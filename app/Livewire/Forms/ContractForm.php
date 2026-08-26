<?php

namespace App\Livewire\Forms;

use App\Enums\SalaryPer;
use App\Models\Contract;
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

    public ?string $attending_notes_ar = null;

    public ?string $attending_notes_en = null;

    public ?string $work_hours_from = '09:00';

    public ?string $work_hours_to = '17:00';

    // ── Pay
    public int $currency = 1;

    public mixed $salary = null;

    public int $salary_per = 1;

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
        $this->weekly_hours = 40;
        $this->currency = 1; // SYP
        $this->salary_per = SalaryPer::Month;
        $this->terminated_at = null;
        $this->termination_reason_id = null;
        $this->reason_of_termination = null;
        $this->attending_notes_ar = null;
        $this->attending_notes_en = null;
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
        $this->is_active = (bool) $contract->is_active;

        $this->start_date = $contract->start_date?->toDateString();
        $this->end_date = $contract->end_date?->toDateString();
        $this->terminated_at = $contract->terminated_at?->toDateString();
        $this->termination_reason_id = $contract->termination_reason_id;
        $this->reason_of_termination = $contract->reason_of_termination;
        $this->weekly_hours = (int) $contract->weekly_hours;

        $this->currency = (int) $contract->currency;
        $this->salary = $contract->salary;
        $this->salary_per = $contract->salary_per;
        $this->work_days_ar = $contract->work_days_ar;
        $this->work_days_en = $contract->work_days_en;
        $this->attending_notes_ar = $contract->attending_notes_ar;
        $this->attending_notes_en = $contract->attending_notes_en;
        $this->work_hours_from = $contract->work_hours_from ?? $this->work_hours_from;
        $this->work_hours_to = $contract->work_hours_to ?? $this->work_hours_to;
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
            'weekly_hours' => ['required', 'integer', 'min:1'],
            'attending_notes_ar' => ['nullable', 'string', 'max:255'],
            'attending_notes_en' => ['nullable', 'string', 'max:255'],
        ]);
    }

    public function validatePay(): array
    {
        return $this->validate([
            'currency' => ['required', 'integer', Rule::in([0, 1, 2, 3])],
            'salary' => ['required', 'numeric', 'min:0'],
            'salary_per' => ['required', 'integer'],
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

            return $this->contract;
        });
    }

    /**
     * Create from data that the owning component has already fully validated.
     */
    public function create(): Contract
    {
        return DB::transaction(fn (): Contract => $this->contract = $this->insert());
    }

    /**
     * Update a specific contract instance without re-running validation
     * (callers are responsible for validating beforehand).
     */
    public function update(Contract $contract): bool
    {
        return DB::transaction(fn () => $contract->update($this->buildPayload()));
    }

    public function saveIdentity(): void
    {
        $this->validateIdentity();

        $this->contract?->update([
            'employee_id' => $this->employee_id,
            'position_id' => $this->position_id,
            'contract_type_id' => $this->contract_type_id,
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
            'attending_notes_ar' => filled($this->attending_notes_ar) ? trim($this->attending_notes_ar) : null,
            'attending_notes_en' => filled($this->attending_notes_en) ? trim($this->attending_notes_en) : null,
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

    private function buildPayload(): array
    {
        return [
            'employee_id' => $this->employee_id,
            'position_id' => $this->position_id,
            'contract_type_id' => $this->contract_type_id,
            'is_active' => (bool) $this->is_active,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'terminated_at' => $this->terminated_at ?? null,
            'termination_reason_id' => $this->termination_reason_id,
            'reason_of_termination' => $this->reason_of_termination ?? null,
            'weekly_hours' => (int) $this->weekly_hours,
            'work_days_ar' => $this->work_days_ar,
            'work_days_en' => $this->work_days_en,
            'attending_notes_ar' => filled($this->attending_notes_ar) ? trim($this->attending_notes_ar) : null,
            'attending_notes_en' => filled($this->attending_notes_en) ? trim($this->attending_notes_en) : null,
            'work_hours_from' => $this->work_hours_from,
            'work_hours_to' => $this->work_hours_to,
            'currency' => (int) $this->currency,
            'salary' => $this->salary,
            'salary_per' => (string) $this->salary_per,
        ];
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
}
