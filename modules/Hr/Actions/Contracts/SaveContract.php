<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Contracts;

use Closure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractPlace;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Enums\Currency;
use Modules\Hr\Enums\PayMode;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Enums\SessionDuration;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Contracts\ContractOptions;
use Modules\Hr\Support\Contracts\PositionSeats;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * Create, edit or renew a contract (HR's ContractForm::save()).
 *
 * The rules are HR's: a draft employee cannot be given a contract; Freelance
 * goes with a service contract and only with it; a term runs at most a year;
 * weekly hours are required unless service; fixed pay needs a figure and a
 * period, flexible pay at least one tier. A role that may not see pay never
 * writes it: an edit keeps what is on file, a renewal copies its source's.
 * Only HR sets auto-renew. Seats on the position move with the contract.
 */
class SaveContract
{
    /**
     * @param  array<string, mixed>  $data  the form's fields (see ManagesContractForm::FIELDS)
     * @param  list<array{condition_en: string, condition_ar: string, amount: mixed}>  $tiers
     */
    public function handle(?Contract $contract, array $data, array $tiers, User $actor, ?Contract $renewalSource = null): Contract
    {
        $renewalSource !== null
            ? Gate::forUser($actor)->authorize('renew', $renewalSource)
            : Gate::forUser($actor)->authorize($contract === null ? 'create' : 'update', $contract ?? Contract::class);

        $payHidden = ! HrAccess::canViewSalary($actor);

        $this->validate($contract, $data, $tiers, $payHidden, $renewalSource);

        $employee = Employee::query()->select(['id', 'city_id', 'record_status'])->findOrFail((int) $data['employee_id']);

        if (! HrAccess::canAccessCity($actor, $employee->city_id)) {
            throw ValidationException::withMessages(['form.employee_id' => __('hr_employees.validation.city_scope')]);
        }

        $saved = DB::transaction(function () use ($contract, $data, $tiers, $actor, $renewalSource, $payHidden): Contract {
            $payload = $this->payload($data, $payHidden, $contract, $renewalSource);

            if ($this->mayWriteAutoRenew($actor, $contract, $renewalSource)) {
                $payload['auto_renew'] = (bool) ($data['auto_renew'] ?? false);
            } elseif ($contract === null) {
                $payload['auto_renew'] = $renewalSource?->auto_renew ?? true;
            }

            $wasHolding = $contract !== null && PositionSeats::holdsSeat($contract->type, $contract->is_active);
            $oldPositionId = $contract?->position_id;

            $target = $contract ?? new Contract;
            $target->fill($payload);

            if (! $target->canAutoRenew()) {
                $target->auto_renew = false;
            }

            if ($target->isTerminated()) {
                $target->is_active = false;
            }

            $holdsNow = PositionSeats::holdsSeat($target->type, $target->is_active);
            $renewalInPlace = $renewalSource !== null && $renewalSource->position_id === (int) $target->position_id;
            $claim = $holdsNow && (! $wasHolding || $oldPositionId !== (int) $target->position_id);

            if ($claim && ! $renewalInPlace) {
                PositionSeats::assertRoom((int) $target->position_id);
            }

            $target->save();

            PositionSeats::move(
                $wasHolding && (! $holdsNow || $oldPositionId !== (int) $target->position_id) ? $oldPositionId : null,
                $claim ? (int) $target->position_id : null,
            );

            $this->syncTiers($target, $tiers, $payHidden, $renewalSource);

            return $target;
        });

        app(ActivateEmployeeForContract::class)->handle($saved);

        return $saved;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $tiers
     *
     * @throws ValidationException
     */
    private function validate(?Contract $contract, array $data, array $tiers, bool $payHidden, ?Contract $renewalSource): void
    {
        $category = ContractOptions::category(isset($data['contract_type_id']) ? (int) $data['contract_type_id'] : null);
        $maxEnd = filled($data['start_date'] ?? null) ? Carbon::parse($data['start_date'])->addYear()->toDateString() : null;
        $isFixedSchedule = (int) ($data['schedule_mode'] ?? 0) === ScheduleMode::Fixed->value;
        $isFixedPay = (int) ($data['pay_mode'] ?? 0) === PayMode::Fixed->value;

        $rules = [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id'), $this->employeeIsComplete()],
            'position_id' => ['required', 'integer', Rule::exists('positions', 'id')],
            'contract_type_id' => ['required', 'integer', Rule::exists('contract_types', 'id')],
            'type' => ['required', 'integer', Rule::in([ContractTypeEnum::FullTime->value, ContractTypeEnum::PartTime->value, ContractTypeEnum::Freelance->value]), $this->typeMatchesCategory($category)],
            'place' => ['required', 'integer', Rule::in([ContractPlace::Onsite->value, ContractPlace::Online->value])],
            'is_active' => ['boolean'],
            'start_date' => ['required', 'date'],
            'end_date' => array_values(array_filter(['required', 'date', 'after_or_equal:start_date', $maxEnd ? 'before_or_equal:'.$maxEnd : null])),
            'terminated_at' => ['nullable', 'date', 'after_or_equal:start_date'],
            'termination_reason_id' => ['nullable', 'integer', Rule::exists('termination_reasons', 'id')],
            'reason_of_termination' => ['nullable', 'string', 'max:2000'],
            'weekly_hours' => ['required', 'integer', 'min:0', 'max:168', function (string $attribute, mixed $value, Closure $fail) use ($category): void {
                if ($category !== 'service' && (int) $value < 1) {
                    $fail(__('hr_contracts.errors.weekly_hours_required'));
                }
            }],
            'schedule_mode' => ['required', 'integer', Rule::in([ScheduleMode::Fixed->value, ScheduleMode::Flexible->value])],
            'work_hours_from' => [Rule::requiredIf($isFixedSchedule), 'nullable', 'date_format:H:i'],
            'work_hours_to' => [Rule::requiredIf($isFixedSchedule), 'nullable', 'date_format:H:i', 'after:work_hours_from'],
            'work_hours_2_from' => ['nullable', 'date_format:H:i', 'required_with:work_hours_2_to'],
            'work_hours_2_to' => ['nullable', 'date_format:H:i', 'required_with:work_hours_2_from', 'after:work_hours_2_from'],
            'work_day_from' => ['nullable', 'integer', 'between:0,6', 'required_with:work_day_to'],
            'work_day_to' => ['nullable', 'integer', 'between:0,6', 'gte:work_day_from', 'required_with:work_day_from'],
            'flex_days_per_week' => ['nullable', 'integer', 'between:1,7'],
            'flex_hours_per_week' => ['nullable', 'integer', 'min:1', 'max:168'],
            'flex_hours_per_month' => ['nullable', 'integer', 'between:1,300'],
            'flex_custom_en' => ['nullable', 'string', 'max:255'],
            'flex_custom_ar' => ['nullable', 'string', 'max:255'],
        ];

        if ($payHidden) {
            // Nothing about pay is writable; there must be a stored clause to keep.
            if ($contract === null && $renewalSource === null) {
                throw ValidationException::withMessages(['form.salary' => __('hr_contracts.errors.pay_hidden_new')]);
            }
        } else {
            $rules += [
                'pay_mode' => ['required', 'integer', Rule::in([PayMode::Fixed->value, PayMode::Flexible->value])],
                'currency' => ['required', 'integer', Rule::in(array_map(fn (Currency $c): int => $c->value, Currency::cases()))],
                'salary' => [Rule::requiredIf($isFixedPay), 'nullable', 'numeric', 'min:0', 'max:999999999'],
                'salary_per' => [Rule::requiredIf($isFixedPay), 'nullable', 'integer', Rule::in([SalaryPer::Month->value, SalaryPer::Hour->value, SalaryPer::Session->value])],
                'session_duration' => [
                    Rule::requiredIf($isFixedPay && (int) ($data['salary_per'] ?? 0) === SalaryPer::Session->value),
                    'nullable', 'integer',
                    Rule::in([SessionDuration::Hour->value, SessionDuration::HourAndHalf->value, SessionDuration::TwoHours->value]),
                ],
            ];
        }

        $this->throwPrefixed(Validator::make($data, $rules, [], $this->attributes()));

        if (! $payHidden && ! $isFixedPay) {
            $this->throwPrefixed(Validator::make(['tiers' => $tiers], [
                'tiers' => ['required', 'array', 'min:1', 'max:20'],
                'tiers.*.condition_en' => ['required', 'string', 'max:255'],
                'tiers.*.condition_ar' => ['required', 'string', 'max:255'],
                'tiers.*.amount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            ], [], [
                'tiers' => __('hr_contracts.fields.pay_tiers'),
                'tiers.*.condition_en' => __('hr_contracts.fields.condition_en'),
                'tiers.*.condition_ar' => __('hr_contracts.fields.condition_ar'),
                'tiers.*.amount' => __('hr_contracts.fields.amount'),
            ]), '');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, bool $payHidden, ?Contract $contract, ?Contract $renewalSource): array
    {
        $fixed = (int) $data['schedule_mode'] === ScheduleMode::Fixed->value;
        $nullable = fn (string $key): mixed => filled($data[$key] ?? null) ? $data[$key] : null;
        $int = fn (string $key): ?int => filled($data[$key] ?? null) ? (int) $data[$key] : null;

        $payload = [
            'employee_id' => (int) $data['employee_id'],
            'position_id' => (int) $data['position_id'],
            'contract_type_id' => (int) $data['contract_type_id'],
            'type' => (int) $data['type'],
            'place' => (int) $data['place'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'terminated_at' => $nullable('terminated_at'),
            'termination_reason_id' => $int('termination_reason_id'),
            'reason_of_termination' => $nullable('reason_of_termination'),
            'weekly_hours' => (int) $data['weekly_hours'],
            'work_days_en' => $nullable('work_days_en'),
            'work_days_ar' => $nullable('work_days_ar'),
            'schedule_mode' => (int) $data['schedule_mode'],
            'work_hours_from' => $fixed ? $nullable('work_hours_from') : null,
            'work_hours_to' => $fixed ? $nullable('work_hours_to') : null,
            'work_hours_2_from' => $fixed ? $nullable('work_hours_2_from') : null,
            'work_hours_2_to' => $fixed ? $nullable('work_hours_2_to') : null,
            'work_day_from' => $fixed ? $int('work_day_from') : null,
            'work_day_to' => $fixed ? $int('work_day_to') : null,
            'show_days_per_week' => $fixed && (bool) ($data['show_days_per_week'] ?? false),
            'show_hours_per_week' => $fixed && (bool) ($data['show_hours_per_week'] ?? false),
            'show_hours_per_day' => $fixed && (bool) ($data['show_hours_per_day'] ?? false),
            'show_day_range' => $fixed && (bool) ($data['show_day_range'] ?? false),
            'flex_days_per_week' => $fixed ? null : $int('flex_days_per_week'),
            'flex_hours_per_week' => $fixed ? null : $int('flex_hours_per_week'),
            'flex_hours_per_month' => $fixed ? null : $int('flex_hours_per_month'),
            'flex_varies_by_requirements' => ! $fixed && (bool) ($data['flex_varies_by_requirements'] ?? false),
            'flex_coordinated_with_manager' => ! $fixed && (bool) ($data['flex_coordinated_with_manager'] ?? false),
            'flex_custom_en' => $fixed ? null : $nullable('flex_custom_en'),
            'flex_custom_ar' => $fixed ? null : $nullable('flex_custom_ar'),
        ];

        if ($payHidden) {
            // An edit leaves the pay columns alone; a renewal copies its source's clause.
            if ($contract === null && $renewalSource !== null) {
                $source = Contract::query()->select(['id', 'pay_mode', 'currency', 'salary', 'salary_per', 'session_duration'])->find($renewalSource->id);

                foreach (['pay_mode', 'currency', 'salary', 'salary_per', 'session_duration'] as $key) {
                    $payload[$key] = $source?->{$key};
                }
            }

            return $payload;
        }

        $fixedPay = (int) $data['pay_mode'] === PayMode::Fixed->value;

        return $payload + [
            'pay_mode' => (int) $data['pay_mode'],
            'currency' => (int) $data['currency'],
            'salary' => $fixedPay ? (string) $data['salary'] : null,
            'salary_per' => $fixedPay ? (int) $data['salary_per'] : SalaryPer::Unknown->value,
            'session_duration' => $fixedPay && (int) $data['salary_per'] === SalaryPer::Session->value ? (int) $data['session_duration'] : null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $tiers
     */
    private function syncTiers(Contract $contract, array $tiers, bool $payHidden, ?Contract $renewalSource): void
    {
        if ($payHidden) {
            if ($renewalSource !== null && ! $contract->payTiers()->exists()) {
                foreach ($renewalSource->payTiers()->get(['condition_en', 'condition_ar', 'amount', 'sort_order']) as $tier) {
                    $contract->payTiers()->create($tier->only(['condition_en', 'condition_ar', 'amount', 'sort_order']));
                }
            }

            return;
        }

        $contract->payTiers()->delete();

        if ((int) $contract->pay_mode !== PayMode::Flexible->value) {
            return;
        }

        foreach (array_values($tiers) as $index => $tier) {
            $contract->payTiers()->create([
                'condition_en' => trim((string) $tier['condition_en']),
                'condition_ar' => trim((string) $tier['condition_ar']),
                'amount' => $tier['amount'],
                'sort_order' => $index,
            ]);
        }
    }

    private function mayWriteAutoRenew(User $actor, ?Contract $contract, ?Contract $renewalSource): bool
    {
        return match (true) {
            $contract !== null => Gate::forUser($actor)->allows('manageAutoRenew', $contract),
            $renewalSource !== null => Gate::forUser($actor)->allows('manageAutoRenew', $renewalSource),
            default => HrAccess::is($actor, 'HR') && $actor->can('contracts.create'),
        };
    }

    private function employeeIsComplete(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (Employee::query()->whereKey($value)->drafts()->exists()) {
                $fail(__('hr_contracts.errors.employee_incomplete'));
            }
        };
    }

    private function typeMatchesCategory(string $category): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($category): void {
            $isFreelance = (int) $value === ContractTypeEnum::Freelance->value;

            if ($category === 'service' && ! $isFreelance) {
                $fail(__('hr_contracts.errors.service_requires_freelance'));
            } elseif ($category !== 'service' && $isFreelance) {
                $fail(__('hr_contracts.errors.freelance_requires_service'));
            }
        };
    }

    /**
     * @return array<string, string>
     */
    private function attributes(): array
    {
        $names = [];

        foreach (array_keys((array) __('hr_contracts.fields')) as $field) {
            $names[$field] = __('hr_contracts.fields.'.$field);
        }

        return $names;
    }

    /**
     * The form binds its fields under form.* (and tiers.* as is).
     *
     * @throws ValidationException
     */
    private function throwPrefixed(\Illuminate\Validation\Validator $validator, string $prefix = 'form.'): void
    {
        if (! $validator->fails()) {
            return;
        }

        $messages = [];

        foreach ($validator->errors()->messages() as $key => $list) {
            $messages[$prefix.$key] = $list;
        }

        throw ValidationException::withMessages($messages);
    }
}
