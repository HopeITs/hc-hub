<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Contracts;

use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Enums\Currency;
use Modules\Hr\Enums\PayMode;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Enums\SessionDuration;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractAmendment;
use Modules\Hr\Support\Contracts\ContractOptions;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * Save the amendment form (HR's ContractAmend::submit()): the schedule, the
 * pay and the full/part-time choice, checked with the contract form's own
 * rules, recorded as a draft amendment — or as a new wording of the draft
 * being edited. The contract itself does not change until approval.
 *
 * Everything else about the contract is fixed: only the amendable terms are
 * read from $data. A role that may not see pay leaves pay as it is.
 */
class DraftContractAmendment
{
    public function __construct(private ContractAmendmentService $amendments) {}

    /**
     * @param  array<string, mixed>  $data  the form's fields (ManagesContractForm::FIELDS) plus effective_from and reason
     * @param  list<array{condition_en: string, condition_ar: string, amount: mixed}>  $tiers
     *
     * @throws ValidationException
     */
    public function handle(Contract $contract, ?ContractAmendment $draft, array $data, array $tiers, User $actor): ContractAmendment
    {
        $draft !== null
            ? Gate::forUser($actor)->authorize('decideAmendment', $draft)
            : Gate::forUser($actor)->authorize('amend', $contract);

        $payHidden = ! HrAccess::canViewSalary($actor);
        $category = ContractOptions::category($contract->contract_type_id);

        $this->validate($data, $tiers, $payHidden, $category);

        $changes = $this->changes($data, $tiers, $payHidden, $category);
        $reason = filled($data['reason'] ?? null) ? (string) $data['reason'] : null;

        try {
            return $draft !== null
                ? $this->amendments->redraft($draft, $changes, (string) $data['effective_from'], $reason, $actor)
                : $this->amendments->draft($contract, $changes, (string) $data['effective_from'], $reason, $actor);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $key): array => [$this->errorKey($key) => $messages])->all()
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $tiers
     */
    private function validate(array $data, array $tiers, bool $payHidden, string $category): void
    {
        $isFixedSchedule = (int) ($data['schedule_mode'] ?? 0) === ScheduleMode::Fixed->value;
        $isFixedPay = (int) ($data['pay_mode'] ?? 0) === PayMode::Fixed->value;

        $rules = [
            'effective_from' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'integer', Rule::in([ContractTypeEnum::FullTime->value, ContractTypeEnum::PartTime->value, ContractTypeEnum::Freelance->value]), $this->typeMatchesCategory($category)],
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

        if (! $payHidden) {
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

        $attributes = ['effective_from' => __('hr_contract_amendments.fields.effective_from'), 'reason' => __('hr_contract_amendments.fields.reason')];

        foreach (array_keys((array) __('hr_contracts.fields')) as $field) {
            $attributes[$field] = __('hr_contracts.fields.'.$field);
        }

        $validator = Validator::make($data, $rules, [], $attributes);

        if ($validator->fails()) {
            throw ValidationException::withMessages(
                collect($validator->errors()->messages())->mapWithKeys(fn (array $messages, string $key): array => [$this->errorKey($key) => $messages])->all()
            );
        }

        if (! $payHidden && ! $isFixedPay) {
            Validator::make(['tiers' => $tiers], [
                'tiers' => ['required', 'array', 'min:1', 'max:20'],
                'tiers.*.condition_en' => ['required', 'string', 'max:255'],
                'tiers.*.condition_ar' => ['required', 'string', 'max:255'],
                'tiers.*.amount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            ], [], [
                'tiers' => __('hr_contracts.fields.pay_tiers'),
                'tiers.*.condition_en' => __('hr_contracts.fields.condition_en'),
                'tiers.*.condition_ar' => __('hr_contracts.fields.condition_ar'),
                'tiers.*.amount' => __('hr_contracts.fields.amount'),
            ])->validate();
        }
    }

    /**
     * The amendable terms as the form leaves them, normalised the way
     * SaveContract writes a contract.
     *
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $tiers
     * @return array<string, mixed>
     */
    private function changes(array $data, array $tiers, bool $payHidden, string $category): array
    {
        $fixed = (int) $data['schedule_mode'] === ScheduleMode::Fixed->value;
        $nullable = fn (string $key): mixed => filled($data[$key] ?? null) ? $data[$key] : null;
        $int = fn (string $key): ?int => filled($data[$key] ?? null) ? (int) $data[$key] : null;

        $changes = [
            'type' => (int) $data['type'],
            'weekly_hours' => (int) $data['weekly_hours'],
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
            return $changes;
        }

        $fixedPay = (int) $data['pay_mode'] === PayMode::Fixed->value;

        return $changes + [
            'pay_mode' => (int) $data['pay_mode'],
            'currency' => (int) $data['currency'],
            'salary' => $fixedPay ? (string) $data['salary'] : null,
            'salary_per' => $fixedPay ? (int) $data['salary_per'] : SalaryPer::Unknown->value,
            'session_duration' => $fixedPay && (int) $data['salary_per'] === SalaryPer::Session->value ? (int) $data['session_duration'] : null,
            'pay_tiers' => $fixedPay ? [] : array_values(array_map(fn (array $tier): array => [
                'condition_en' => trim((string) ($tier['condition_en'] ?? '')),
                'condition_ar' => trim((string) ($tier['condition_ar'] ?? '')),
                'amount' => $tier['amount'] ?? null,
            ], $tiers)),
        ];
    }

    /**
     * The page binds the contract terms under form.* and its own two fields
     * at the top level; anything about the contract as a whole goes on terms.
     */
    private function errorKey(string $key): string
    {
        return match (true) {
            in_array($key, ['effective_from', 'reason', 'terms', 'amendment'], true) => $key,
            in_array($key, ['contract'], true) => 'terms',
            str_starts_with($key, 'tiers') => $key,
            default => 'form.'.$key,
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
}
