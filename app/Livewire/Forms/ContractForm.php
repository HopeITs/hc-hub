<?php

namespace App\Livewire\Forms;

use App\Enums\ContractPlace;
use App\Enums\ContractType as ContractTypeEnum;
use App\Enums\PayMode;
use App\Enums\SalaryPer;
use App\Enums\ScheduleMode;
use App\Enums\SessionDuration;
use App\Models\Contract;
use App\Models\ContractPayTier;
use App\Models\ContractType;
use App\Models\Position;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class ContractForm extends Form
{
    // ── Model reference
    public ?Contract $contract = null;

    /**
     * Set when this form is drafting the successor of an existing contract.
     *
     * Locked: this marker is what waives the position capacity check, so a
     * client able to set it could point an ordinary create at any contract on a
     * full position and overflow max_emp. Only the server may assign it.
     */
    #[Locked]
    public ?int $renewal_source_id = null;

    // ── Identity
    public ?int $employee_id = null;

    public ?int $position_id = null;

    public ?int $contract_type_id = null;

    public int $type = 1;

    public int $place = 1;

    public bool $is_active = true;

    // ── Terms
    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?string $terminated_at = null;

    public ?string $reason_of_termination = null;

    public ?int $termination_reason_id = null;

    public int $weekly_hours = 40;

    // ── Schedule
    public mixed $work_days_ar = null;

    public mixed $work_days_en = null;

    // 1 = fixed, 2 = flexible — see App\Enums\ScheduleMode.
    public int $schedule_mode = 1;

    // -- Fixed mode
    public ?string $work_hours_from = '09:00';

    public ?string $work_hours_to = '17:00';

    /** Optional second daily shift, e.g. 9:00-13:00 and 17:00-21:00. Both null, or both set. */
    public ?string $work_hours_2_from = null;

    public ?string $work_hours_2_to = null;

    public ?int $work_day_from = null;

    public ?int $work_day_to = null;

    /** Whether the printed contract states the computed "N days/week" / "M hours/week" — fixed mode only, never edited directly. */
    public bool $show_days_per_week = false;

    public bool $show_hours_per_week = false;

    // -- Flexible mode
    public ?int $flex_days_per_week = null;

    public ?int $flex_hours_per_week = null;

    public ?int $flex_hours_per_month = null;

    public bool $flex_varies_by_requirements = false;

    public bool $flex_coordinated_with_manager = false;

    public ?string $flex_custom_en = null;

    public ?string $flex_custom_ar = null;

    // ── Pay
    // 1 = fixed, 2 = flexible — see App\Enums\PayMode.
    public int $pay_mode = 1;

    public int $currency = 1;

    // -- Fixed mode
    public mixed $salary = null;

    public int $salary_per = 1;

    /** Only meaningful when salary_per is Session — see App\Enums\SessionDuration. */
    public ?int $session_duration = null;

    /**
     * -- Flexible mode: an unbounded "Add condition" repeater instead of one
     * flat figure, e.g. "if teaching 60h/month" => 200, "70h/month" => 300.
     * Each row: ['condition_en' => string, 'condition_ar' => string, 'amount' => numeric].
     *
     * @var array<int, array{condition_en: string, condition_ar: string, amount: mixed}>
     */
    public array $pay_tiers = [];

    // ────────────────────────────────────────────────
    // Lifecycle hooks
    // ────────────────────────────────────────────────

    /**
     * Recompute the term when the contract type changes.
     *
     * $anchor is the date the term must start from. A renewal passes the date
     * its predecessor hands over on; without it the term would be reset to the
     * start of next month, silently back-dating the renewal to before the
     * contract it replaces has even ended. A fresh contract passes nothing and
     * keeps the original "starts next month" default.
     */
    public function updatedContractTypeId(mixed $value, ?string $anchor = null): void
    {
        if (! $value) {
            return;
        }

        $start = Carbon::parse($anchor ?? Carbon::now()->addMonthNoOverflow()->startOfMonth());

        $this->start_date = $start->toDateString();

        // Contract types 3 & 4 are trial contracts (3-month duration)
        if (in_array((int) $value, [3, 4])) {
            $this->end_date = $start->copy()->addMonthsNoOverflow(3)->toDateString();

            return;
        }

        // A fresh contract runs to the end of the current year. A renewal has
        // no such anchor to fall back on — the current year may already be
        // behind it — so it runs a full year from its own start instead, which
        // is also the longest term validateTerms() accepts.
        $this->end_date = $anchor
            ? $start->copy()->addYear()->subDay()->toDateString()
            : Carbon::now()->endOfYear()->toDateString();
    }

    // ────────────────────────────────────────────────
    // Initialisation
    // ────────────────────────────────────────────────

    public function setDefaults(): void
    {

        $this->is_active = true;
        $this->type = 1;
        $this->place = 1;
        $this->weekly_hours = 40;
        $this->currency = 1; // SYP
        $this->salary_per = SalaryPer::Month;
        $this->terminated_at = null;
        $this->termination_reason_id = null;
        $this->reason_of_termination = null;

        $this->schedule_mode = ScheduleMode::Fixed;
        $this->work_hours_2_from = null;
        $this->work_hours_2_to = null;
        $this->work_day_from = null;
        $this->work_day_to = null;
        $this->show_days_per_week = false;
        $this->show_hours_per_week = false;
        $this->flex_days_per_week = null;
        $this->flex_hours_per_week = null;
        $this->flex_hours_per_month = null;
        $this->flex_varies_by_requirements = false;
        $this->flex_coordinated_with_manager = false;
        $this->flex_custom_en = null;
        $this->flex_custom_ar = null;

        $this->pay_mode = PayMode::Fixed;
        $this->session_duration = null;
        $this->pay_tiers = [];
    }

    public function setContract(Contract $contract): void
    {
        $this->contract = $contract;
        $this->fillFromModel($contract);
    }

    /**
     * Draft the successor of $source: every term carried over, only the dates
     * replaced.
     *
     * Nulling `contract` is what makes the shared save()/create() path insert a
     * new row instead of updating the source, and clearing the termination trio
     * stops a contract that was terminated and later reactivated from passing a
     * stale termination on to its successor.
     */
    public function fillFromRenewal(Contract $source, string $start, string $end): void
    {
        $this->fillFromModel($source);

        $this->contract = null;
        $this->renewal_source_id = $source->id;

        $this->is_active = true;
        $this->terminated_at = null;
        $this->termination_reason_id = null;
        $this->reason_of_termination = null;

        $this->start_date = $start;
        $this->end_date = $end;
    }

    public function fillFromModel(Contract $contract): void
    {
        $this->employee_id = $contract->employee_id;
        $this->position_id = $contract->position_id;
        $this->contract_type_id = $contract->contract_type_id;
        $this->type = (int) $contract->type;
        $this->place = (int) $contract->place;
        $this->is_active = (bool) $contract->is_active;

        $this->start_date = $contract->start_date?->toDateString();
        $this->end_date = $contract->end_date?->toDateString();
        $this->terminated_at = $contract->terminated_at?->toDateString();
        $this->termination_reason_id = $contract->termination_reason_id;
        $this->reason_of_termination = $contract->reason_of_termination;
        $this->weekly_hours = (int) $contract->weekly_hours;

        $this->pay_mode = (int) ($contract->pay_mode ?? PayMode::Fixed);
        $this->currency = (int) $contract->currency;
        $this->salary = $contract->salary;
        $this->salary_per = $contract->salary_per;
        $this->session_duration = $contract->session_duration;
        $this->pay_tiers = $contract->payTiers
            ->map(fn (ContractPayTier $tier): array => [
                'condition_en' => $tier->condition_en,
                'condition_ar' => $tier->condition_ar,
                'amount' => $tier->amount,
            ])
            ->all();
        $this->work_days_ar = $contract->work_days_ar;
        $this->work_days_en = $contract->work_days_en;
        $this->work_hours_from = $this->timeForInput($contract->work_hours_from, $this->work_hours_from);
        $this->work_hours_to = $this->timeForInput($contract->work_hours_to, $this->work_hours_to);

        $this->schedule_mode = (int) ($contract->schedule_mode ?? ScheduleMode::Fixed);
        $this->work_hours_2_from = $contract->work_hours_2_from;
        $this->work_hours_2_to = $contract->work_hours_2_to;
        $this->work_day_from = $contract->work_day_from;
        $this->work_day_to = $contract->work_day_to;
        $this->show_days_per_week = (bool) $contract->show_days_per_week;
        $this->show_hours_per_week = (bool) $contract->show_hours_per_week;
        $this->flex_days_per_week = $contract->flex_days_per_week;
        $this->flex_hours_per_week = $contract->flex_hours_per_week;
        $this->flex_hours_per_month = $contract->flex_hours_per_month;
        $this->flex_varies_by_requirements = (bool) $contract->flex_varies_by_requirements;
        $this->flex_coordinated_with_manager = (bool) $contract->flex_coordinated_with_manager;
        $this->flex_custom_en = $contract->flex_custom_en;
        $this->flex_custom_ar = $contract->flex_custom_ar;
    }

    // ────────────────────────────────────────────────
    // Validation
    // ────────────────────────────────────────────────

    public function validateIdentity(): array
    {
        return $this->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'position_id' => ['required', 'exists:positions,id', $this->positionCapacityRule()],
            'contract_type_id' => ['required', 'exists:contract_types,id'],
            'type' => ['required', Rule::in([ContractTypeEnum::FullTime, ContractTypeEnum::PartTime, ContractTypeEnum::Freelance]), $this->typeMatchesCategoryRule()],
            'place' => ['required', Rule::in([ContractPlace::Onsite, ContractPlace::Online])],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    public function validateTerms(): array
    {
        $maxEndDate = $this->start_date
            ? Carbon::parse($this->start_date)->addYear()->toDateString()
            : null;

        return $this->validate([
            'start_date' => ['required', 'date'],
            'end_date' => array_filter([
                'required',
                'date',
                'after_or_equal:start_date',
                $maxEndDate ? "before_or_equal:{$maxEndDate}" : null,
            ]),
            'terminated_at' => ['nullable', 'date', 'after_or_equal:start_date'],
            'termination_reason_id' => ['nullable', 'exists:termination_reasons,id'],
            'reason_of_termination' => ['nullable', 'string'],
            // Computed automatically in fixed mode (day range × daily hours) and
            // taken from the "hours/week" flex box when set; a service contract
            // is the only category allowed to leave it at 0 — LeaveBalanceService
            // already skips leave entitlement entirely for that category.
            'weekly_hours' => ['required', 'integer', 'min:0', $this->weeklyHoursRule()],

            'schedule_mode' => ['required', Rule::in([ScheduleMode::Fixed, ScheduleMode::Flexible])],

            // Not hard-required even in fixed mode: a contract that predates
            // this field (schedule_mode/day range did not exist yet) must
            // still be renewable/editable without being forced to backfill
            // it on the spot. weekly_hours (below) is the real gate — it
            // cannot land on a number without either this range or the flex
            // "hours/week" box, for any category that isn't service.
            'work_day_from' => ['nullable', 'integer', 'between:0,6', 'required_with:work_day_to'],
            'work_day_to' => ['nullable', 'integer', 'between:0,6', 'gte:work_day_from', 'required_with:work_day_from'],
            'work_hours_2_from' => ['nullable', 'date_format:H:i', 'required_with:work_hours_2_to'],
            'work_hours_2_to' => ['nullable', 'date_format:H:i', 'required_with:work_hours_2_from', 'after:work_hours_2_from'],

            'flex_days_per_week' => ['nullable', 'integer', 'between:1,7'],
            'flex_hours_per_week' => ['nullable', 'integer', 'min:1'],
            'flex_hours_per_month' => ['nullable', 'integer', 'between:1,300'],
            'flex_custom_en' => ['nullable', 'string', 'max:255'],
            'flex_custom_ar' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * Every category except service must land on an explicit weekly-hours
     * figure one way or another (computed from the fixed day/hour range, or
     * typed into the flex "hours/week" box) — service is the one category
     * LeaveBalanceService exempts from leave entitlement entirely, so a
     * contract that only says "varies" / "coordinated with manager" / free
     * text has nothing to compute a number from, and that is fine only there.
     */
    private function weeklyHoursRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $category = ContractType::find($this->contract_type_id)?->category;

            if ($category !== 'service' && (int) $value < 1) {
                $fail(__('validation.custom.weekly_hours.required_unless_service'));
            }
        };
    }

    public function validatePay(): array
    {
        $isFixed = $this->pay_mode === PayMode::Fixed;

        return $this->validate([
            'pay_mode' => ['required', Rule::in([PayMode::Fixed, PayMode::Flexible])],
            'currency' => ['required', 'integer', Rule::in([0, 1, 2, 3])],

            // -- Fixed mode: one flat figure, plus a session length when paid per session.
            'salary' => [Rule::requiredIf($isFixed), 'nullable', 'numeric', 'min:0'],
            'salary_per' => [Rule::requiredIf($isFixed), 'nullable', 'integer'],
            'session_duration' => [
                Rule::requiredIf($isFixed && (int) $this->salary_per === SalaryPer::Session),
                'nullable', 'integer',
                Rule::in([SessionDuration::Hour, SessionDuration::HourAndHalf, SessionDuration::TwoHours]),
            ],

            // -- Flexible mode: an unbounded repeater instead of one figure —
            // at least one condition, or the mode says nothing at all.
            'pay_tiers' => [Rule::requiredIf(! $isFixed), 'array', $isFixed ? 'nullable' : 'min:1'],
            'pay_tiers.*.condition_en' => ['required_with:pay_tiers', 'string', 'max:255'],
            'pay_tiers.*.condition_ar' => ['required_with:pay_tiers', 'string', 'max:255'],
            'pay_tiers.*.amount' => ['required_with:pay_tiers', 'numeric', 'min:0'],
        ]);
    }

    // ────────────────────────────────────────────────
    // Persistence
    // ────────────────────────────────────────────────

    /**
     * Full validation + create/update inside a transaction.
     */
    public function save(): Contract
    {
        $this->validateIdentity();
        $this->validateTerms();
        $this->validatePay();

        return DB::transaction(function () {
            if ($this->contract) {
                $this->contract->update($this->buildPayload());
            } else {
                $this->contract = $this->insert();
            }

            $this->syncPayTiers($this->contract);

            return $this->contract;
        });
    }

    /**
     * Create from data that the owning component has already fully validated.
     */
    public function create(): Contract
    {
        return DB::transaction(function (): Contract {
            $this->contract = $this->insert();
            $this->syncPayTiers($this->contract);

            return $this->contract;
        });
    }

    /**
     * Update a specific contract instance without re-running validation
     * (callers are responsible for validating beforehand).
     */
    public function update(Contract $contract): bool
    {
        return DB::transaction(function () use ($contract): bool {
            $result = $contract->update($this->buildPayload());
            $this->syncPayTiers($contract);

            return $result;
        });
    }

    public function saveIdentity(): void
    {
        $this->validateIdentity();

        $this->contract?->update([
            'employee_id' => $this->employee_id,
            'position_id' => $this->position_id,
            'contract_type_id' => $this->contract_type_id,
            'type' => $this->type,
            'place' => $this->place,
            'is_active' => (bool) $this->is_active,
        ]);
    }

    public function saveTerms(): void
    {
        $this->validateTerms();

        if (! $this->contract) {
            return;
        }

        $isTerminated = filled($this->terminated_at) || filled($this->termination_reason_id);

        $this->contract->update([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'terminated_at' => $this->terminated_at ?? null,
            'termination_reason_id' => $this->termination_reason_id ?? null,
            'reason_of_termination' => $this->reason_of_termination ?? null,
            'weekly_hours' => (int) $this->weekly_hours,
            'work_days_ar' => $this->work_days_ar,
            'work_days_en' => $this->work_days_en,
            'work_hours_from' => $this->work_hours_from,
            'work_hours_to' => $this->work_hours_to,
            // Automatically deactivate when a termination is recorded
            'is_active' => $isTerminated ? false : $this->is_active,
        ]);
    }

    public function savePay(): void
    {
        $this->validatePay();

        $this->contract?->update([
            'currency' => (int) $this->currency,
            'salary' => $this->salary,
            'salary_per' => (string) $this->salary_per,
        ]);
    }

    // ────────────────────────────────────────────────
    // Private helpers
    // ────────────────────────────────────────────────

    /**
     * Insert a new contract, carrying the transient renewal marker onto the
     * model so ContractObserver::creating() can recognise a renewal in place.
     */
    private function insert(): Contract
    {
        $contract = new Contract($this->buildPayload());
        $contract->renewalSourceId = $this->renewal_source_id;
        $contract->save();

        return $contract;
    }

    /**
     * Replace this contract's pay tiers wholesale with $this->pay_tiers.
     *
     * Delete-then-recreate rather than diffing against existing rows: the
     * repeater has no stable per-row identity the UI needs to preserve
     * (no other table references a tier by id), and the row count is always
     * small, so there is nothing a smarter sync would actually save.
     */
    private function syncPayTiers(Contract $contract): void
    {
        $contract->payTiers()->delete();

        if ($this->pay_mode !== PayMode::Flexible) {
            return;
        }

        foreach ($this->pay_tiers as $index => $tier) {
            $contract->payTiers()->create([
                'condition_en' => trim((string) ($tier['condition_en'] ?? '')),
                'condition_ar' => trim((string) ($tier['condition_ar'] ?? '')),
                'amount' => $tier['amount'] ?? 0,
                'sort_order' => $index,
            ]);
        }
    }

    private function buildPayload(): array
    {
        $isFixed = $this->schedule_mode === ScheduleMode::Fixed;
        $isFixedPay = $this->pay_mode === PayMode::Fixed;

        return [
            'employee_id' => $this->employee_id,
            'position_id' => $this->position_id,
            'contract_type_id' => $this->contract_type_id,
            'type' => $this->type,
            'place' => $this->place,
            'is_active' => (bool) $this->is_active,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'terminated_at' => $this->terminated_at ?? null,
            'termination_reason_id' => $this->termination_reason_id,
            'reason_of_termination' => $this->reason_of_termination ?? null,
            'weekly_hours' => (int) $this->weekly_hours,
            'work_days_ar' => $this->work_days_ar,
            'work_days_en' => $this->work_days_en,
            'work_hours_from' => $isFixed ? $this->work_hours_from : null,
            'work_hours_to' => $isFixed ? $this->work_hours_to : null,

            'schedule_mode' => $this->schedule_mode,
            // Each mode's own fields are cleared when it is not the active one,
            // so a contract switched from flexible to fixed (or back) does not
            // keep a stale value from the mode it left behind.
            'work_hours_2_from' => $isFixed ? $this->work_hours_2_from : null,
            'work_hours_2_to' => $isFixed ? $this->work_hours_2_to : null,
            'work_day_from' => $isFixed ? $this->work_day_from : null,
            'work_day_to' => $isFixed ? $this->work_day_to : null,
            'show_days_per_week' => $isFixed && $this->show_days_per_week,
            'show_hours_per_week' => $isFixed && $this->show_hours_per_week,

            'flex_days_per_week' => $isFixed ? null : $this->flex_days_per_week,
            'flex_hours_per_week' => $isFixed ? null : $this->flex_hours_per_week,
            'flex_hours_per_month' => $isFixed ? null : $this->flex_hours_per_month,
            'flex_varies_by_requirements' => ! $isFixed && $this->flex_varies_by_requirements,
            'flex_coordinated_with_manager' => ! $isFixed && $this->flex_coordinated_with_manager,
            'flex_custom_en' => $isFixed ? null : (filled($this->flex_custom_en) ? trim($this->flex_custom_en) : null),
            'flex_custom_ar' => $isFixed ? null : (filled($this->flex_custom_ar) ? trim($this->flex_custom_ar) : null),

            'pay_mode' => $this->pay_mode,
            // Currency applies to both modes — one flat figure or a list of
            // tiered ones, always in the same currency. The other fields
            // below are each mode's own, cleared when it is not active, same
            // as the schedule fields above.
            'currency' => (int) $this->currency,
            'salary' => $isFixedPay ? $this->salary : null,
            'salary_per' => $isFixedPay ? (string) $this->salary_per : (string) SalaryPer::Unknown,
            'session_duration' => ($isFixedPay && (int) $this->salary_per === SalaryPer::Session)
                ? $this->session_duration
                : null,
        ];
    }

    private function timeForInput(mixed $value, ?string $fallback = null): ?string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        if (is_string($value) && preg_match('/^\d{2}:\d{2}/', $value) === 1) {
            return substr($value, 0, 5);
        }

        return Carbon::parse($value)->format('H:i');
    }

    /**
     * Closure-based validation rule that enforces position capacity, skipping
     * the check whenever the seat is already held by the record this write
     * descends from: an edit that leaves the position alone, or a renewal
     * issued onto the same position as its source. Neither adds headcount, and
     * checking them would make renewing a max_emp = 1 position impossible.
     */
    private function positionCapacityRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $isUnchanged = $this->contract && $this->contract->position_id === (int) $value;

            if ($isUnchanged || Contract::isRenewalOfPosition($this->renewal_source_id, (int) $value)) {
                return;
            }

            $position = Position::query()
                ->select('id', 'max_emp', 'exist_emp')
                ->find($value);

            if ($position && ! $position->canAssign()) {
                $fail(__('This position exceeded the number of max employees.'));
            }
        };
    }

    /**
     * Full/Part time only make sense for a volunteer or trial contract;
     * Freelance only for a service one — the composer's own UI already
     * restricts the choice to match (see contract-composer.js::chooseType()),
     * this is the matching server-side check for a request that bypasses it.
     */
    private function typeMatchesCategoryRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $category = ContractType::find($this->contract_type_id)?->category;

            if ($category === null) {
                return; // contract_type_id's own `exists` rule already reports this.
            }

            $isFreelance = (int) $value === ContractTypeEnum::Freelance;

            if ($category === 'service' && ! $isFreelance) {
                $fail(__('validation.custom.type.service_requires_freelance'));
            } elseif ($category !== 'service' && $isFreelance) {
                $fail(__('validation.custom.type.freelance_requires_service'));
            }
        };
    }
}
