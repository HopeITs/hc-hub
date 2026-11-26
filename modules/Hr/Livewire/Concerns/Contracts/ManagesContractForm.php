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
use Modules\Hr\Support\Contracts\WeekendClause;
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
        'work_hours_2_from', 'work_hours_2_to', 'work_day_from', 'work_day_to', 'work_weekdays', 'show_days_per_week',
        'show_hours_per_week', 'show_hours_per_day', 'show_day_range', 'show_weekend_clause', 'flex_days_per_week', 'flex_hours_per_week',
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

    /** An amendment has been applied: the schedule and pay change only through amendments. */
    #[Locked]
    public bool $termsLocked = false;

    /** One per form load, so a double submit saves once. */
    #[Locked]
    public string $submissionToken = '';

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
            // The days picked one by one (Carbon numbering), instead of the from-to range; empty = the range.
            'work_weekdays' => [],
            'show_days_per_week' => true,
            'show_hours_per_week' => true,
            'show_hours_per_day' => true,
            'show_day_range' => true,
            'show_weekend_clause' => false,
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
                $field === 'work_weekdays' => array_map('strval', $contract->workWeekdays() ?? []),
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

    /**
     * A fixed schedule works out its weekly hours from the days and the
     * hours of each day (picked days, else the from-to day range).
     */
    public function updatedForm(mixed $value, ?string $key = null): void
    {
        $drivers = ['work_weekdays', 'work_hours_from', 'work_hours_to', 'work_hours_2_from', 'work_hours_2_to', 'work_day_from', 'work_day_to'];

        if ($key === null || (int) ($this->form['schedule_mode'] ?? 0) !== ScheduleMode::Fixed->value || ! in_array(explode('.', $key)[0], $drivers, true)) {
            return;
        }

        $picked = Contract::parseWeekdays(is_array($this->form['work_weekdays'] ?? null) ? $this->form['work_weekdays'] : null);
        $from = $this->form['work_day_from'] ?? '';
        $to = $this->form['work_day_to'] ?? '';
        $days = match (true) {
            $picked !== [] => count($picked),
            filled($from) && filled($to) && (int) $to >= (int) $from => (int) $to - (int) $from + 1,
            default => 0,
        };

        $minutes = $this->shiftMinutes('work_hours_from', 'work_hours_to') + $this->shiftMinutes('work_hours_2_from', 'work_hours_2_to');

        if ($days > 0 && $minutes > 0) {
            $this->form['weekly_hours'] = (string) round($days * $minutes / 60);
        }
    }

    private function shiftMinutes(string $fromKey, string $toKey): int
    {
        $from = (string) ($this->form[$fromKey] ?? '');
        $to = (string) ($this->form[$toKey] ?? '');

        if (preg_match('/^\d{2}:\d{2}$/', $from) !== 1 || preg_match('/^\d{2}:\d{2}$/', $to) !== 1) {
            return 0;
        }

        $toMinutes = fn (string $time): int => (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);

        return max(0, $toMinutes($to) - $toMinutes($from));
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
     * The place's day(s) off, which no working-day pick may include (the picker greys them out).
     *
     * @return list<int>
     */
    #[Computed]
    public function placeDaysOff(): array
    {
        $positionId = (int) ($this->form['position_id'] ?? 0);

        if ($positionId === 0) {
            return [];
        }

        $position = Position::query()
            ->with(['program:id,slug', 'center:id,city_id,weekends'])
            ->select(['id', 'program_id', 'center_id', 'city_id'])
            ->find($positionId);

        return app(WeekendClause::class)->placeDaysForPosition($position);
    }

    /**
     * The day(s) off a Study Zone contract on the chosen position may state;
     * empty (no checkbox) for other programs or when no center weekend is set.
     *
     * @return list<int>
     */
    #[Computed]
    public function weekendClauseDays(): array
    {
        $positionId = (int) ($this->form['position_id'] ?? 0);

        if ($positionId === 0) {
            return [];
        }

        $position = Position::query()
            ->with(['program:id,slug', 'center:id,city_id,weekends'])
            ->select(['id', 'program_id', 'center_id', 'city_id'])
            ->find($positionId);

        return app(WeekendClause::class)->daysForPosition($position);
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
