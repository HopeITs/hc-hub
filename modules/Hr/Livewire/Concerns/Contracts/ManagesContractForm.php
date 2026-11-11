<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Contracts;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Hr\Enums\ContractPlace;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Enums\Currency;
use Modules\Hr\Enums\PayMode;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Contracts\ContractOptions;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * The contract form's state: create, edit, or renew (a new contract
 * prefilled from its source, only the dates moved on).
 */
trait ManagesContractForm
{
    public const FIELDS = [
        'employee_id', 'position_id', 'contract_type_id', 'type', 'place', 'is_active', 'auto_renew',
        'start_date', 'end_date', 'terminated_at', 'termination_reason_id', 'reason_of_termination',
        'weekly_hours', 'work_days_en', 'work_days_ar', 'schedule_mode', 'work_hours_from', 'work_hours_to',
        'work_hours_2_from', 'work_hours_2_to', 'work_day_from', 'work_day_to', 'show_days_per_week',
        'show_hours_per_week', 'show_hours_per_day', 'show_day_range', 'flex_days_per_week', 'flex_hours_per_week',
        'flex_hours_per_month', 'flex_varies_by_requirements', 'flex_coordinated_with_manager', 'flex_custom_en',
        'flex_custom_ar', 'pay_mode', 'currency', 'salary', 'salary_per', 'session_duration',
    ];

    private const PAY_FIELDS = ['pay_mode', 'currency', 'salary', 'salary_per', 'session_duration'];

    #[Locked]
    public ?int $contractId = null;

    #[Locked]
    public ?int $renewalSourceId = null;

    #[Locked]
    public bool $payHidden = false;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var list<array{condition_en: string, condition_ar: string, amount: string}> */
    public array $tiers = [];

    public string $programFilter = '';

    protected function fillDefaults(?int $employeeId): void
    {
        $start = now()->addMonthNoOverflow()->startOfMonth();

        $this->form = [
            'employee_id' => $employeeId === null ? '' : (string) $employeeId,
            'position_id' => '',
            'contract_type_id' => '',
            'type' => (string) ContractTypeEnum::FullTime->value,
            'place' => (string) ContractPlace::Onsite->value,
            'is_active' => true,
            'auto_renew' => true,
            'start_date' => $start->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'terminated_at' => '',
            'termination_reason_id' => '',
            'reason_of_termination' => '',
            'weekly_hours' => '40',
            'work_days_en' => '',
            'work_days_ar' => '',
            'schedule_mode' => (string) ScheduleMode::Fixed->value,
            'work_hours_from' => '09:00',
            'work_hours_to' => '17:00',
            'work_hours_2_from' => '',
            'work_hours_2_to' => '',
            'work_day_from' => '',
            'work_day_to' => '',
            'show_days_per_week' => true,
            'show_hours_per_week' => true,
            'show_hours_per_day' => true,
            'show_day_range' => true,
            'flex_days_per_week' => '',
            'flex_hours_per_week' => '',
            'flex_hours_per_month' => '',
            'flex_varies_by_requirements' => false,
            'flex_coordinated_with_manager' => false,
            'flex_custom_en' => '',
            'flex_custom_ar' => '',
            'pay_mode' => (string) PayMode::Fixed->value,
            'currency' => (string) Currency::SYP->value,
            'salary' => '',
            'salary_per' => (string) SalaryPer::Month->value,
            'session_duration' => '',
        ];
        $this->tiers = [];
    }

    protected function fillFromContract(Contract $contract): void
    {
        $this->fillDefaults(null);

        foreach (self::FIELDS as $field) {
            if ($this->payHidden && in_array($field, self::PAY_FIELDS, true)) {
                continue;
            }

            $value = $contract->getAttribute($field);

            $this->form[$field] = match (true) {
                is_bool($value) => $value,
                $value instanceof \DateTimeInterface => $value->format('Y-m-d'),
                in_array($field, ['work_hours_from', 'work_hours_to', 'work_hours_2_from', 'work_hours_2_to'], true) => $value === null ? '' : substr((string) $value, 0, 5),
                default => (string) ($value ?? ''),
            };
        }

        if ((int) $contract->type === ContractTypeEnum::Draft->value) {
            $this->form['type'] = (string) ContractTypeEnum::FullTime->value;
        }

        $this->tiers = $this->payHidden ? [] : $contract->payTiers()
            ->orderBy('sort_order')
            ->get(['condition_en', 'condition_ar', 'amount'])
            ->map(fn ($tier): array => ['condition_en' => (string) $tier->condition_en, 'condition_ar' => (string) $tier->condition_ar, 'amount' => (string) $tier->amount])
            ->all();
    }

    protected function fillFromRenewal(Contract $source): void
    {
        $this->fillFromContract($source);

        [$start, $end] = $source->renewalWindow();

        $this->form['start_date'] = $start;
        $this->form['end_date'] = $end;
        $this->form['is_active'] = true;
        $this->form['terminated_at'] = '';
        $this->form['termination_reason_id'] = '';
        $this->form['reason_of_termination'] = '';
    }

    /**
     * A new contract type moves the term: a trial runs three months, anything
     * else to the end of the year (a renewal: a full year from its start).
     */
    public function updatedFormContractTypeId(mixed $value): void
    {
        if (! filled($value) || $this->contractId !== null) {
            return;
        }

        $start = Carbon::parse($this->form['start_date'] ?: now()->addMonthNoOverflow()->startOfMonth());
        $category = ContractOptions::category((int) $value);

        $this->form['end_date'] = match (true) {
            $category === 'trial' => $start->copy()->addMonthsNoOverflow(3)->toDateString(),
            $this->renewalSourceId !== null => $start->copy()->addYear()->subDay()->toDateString(),
            default => now()->endOfYear()->max($start)->toDateString(),
        };

        $this->form['type'] = (string) ($category === 'service' ? ContractTypeEnum::Freelance->value : ContractTypeEnum::FullTime->value);

        if ($category === 'trial') {
            $this->form['auto_renew'] = false;
        }
    }

    public function addTier(): void
    {
        if (count($this->tiers) < 20) {
            $this->tiers[] = ['condition_en' => '', 'condition_ar' => '', 'amount' => ''];
        }
    }

    public function removeTier(int $index): void
    {
        unset($this->tiers[$index]);
        $this->tiers = array_values($this->tiers);
    }

    /**
     * Positions in the employee's city (or the viewer's cities), with room
     * to spare, plus the one already chosen.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function positionOptions(): array
    {
        $cityId = filled($this->form['employee_id'] ?? null)
            ? Employee::query()->whereKey((int) $this->form['employee_id'])->value('city_id')
            : null;
        $cities = $cityId !== null ? [(int) $cityId] : HrAccess::cityIds(Auth::user());
        $current = (int) ($this->form['position_id'] ?? 0);

        return Position::query()
            ->select(['id', 'title_en', 'title_ar', 'program_id', 'max_emp', 'exist_emp'])
            ->where('is_active', true)
            ->when($cities !== null, fn ($query) => $query->inCity($cities))
            ->when($this->programFilter !== '', fn ($query) => $query->where('program_id', (int) $this->programFilter))
            ->where(fn ($query) => $query->whereColumn('exist_emp', '<', 'max_emp')->orWhere('id', $current))
            ->orderBy('title_en')
            ->limit(300)
            ->get()
            ->mapWithKeys(fn (Position $position): array => [$position->id => $position->title.' ('.(int) $position->exist_emp.'/'.(int) $position->max_emp.')'])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return $this->form;
    }

    protected function category(): string
    {
        return ContractOptions::category(filled($this->form['contract_type_id'] ?? null) ? (int) $this->form['contract_type_id'] : null);
    }
}
