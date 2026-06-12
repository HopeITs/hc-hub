<?php

namespace App\Livewire\Forms;

use App\Enums\SalaryPer;
use App\Models\Contract;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ContractForm extends Form
{
    // ── Model reference
    public ?Contract $contract = null;

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
    public string $work_hours_from = '09:00';
    public string $work_hours_to = '17:00';

    // ── Pay
    public int $currency = 1;
    public mixed $salary = null;
    public int $salary_per = 1;

    // ────────────────────────────────────────────────
    // Lifecycle hooks
    // ────────────────────────────────────────────────

    public function updatedContractTypeId(mixed $value): void
    {
        if (! $value) {
            return;
        }

        $nextMonthStart = Carbon::now()->addMonthNoOverflow()->startOfMonth()->toDateString();

        $this->start_date = $nextMonthStart;

        // Contract types 3 & 4 are trial contracts (3-month duration)
        $this->end_date = in_array((int) $value, [3, 4])
            ? Carbon::parse($nextMonthStart)->addMonthsNoOverflow(3)->toDateString()
            : Carbon::now()->endOfYear()->toDateString();
    }

    // ────────────────────────────────────────────────
    // Initialisation
    // ────────────────────────────────────────────────

    public function setDefaults(): void
    {

        $this->is_active             = true;
        $this->weekly_hours          = 40;
        $this->currency              = 1; // SYP
        $this->salary_per            =  SalaryPer::Month;
        $this->terminated_at         = null;
        $this->termination_reason_id = null;
        $this->reason_of_termination = null;
    }

    public function setContract(Contract $contract): void
    {
        $this->contract = $contract;
        $this->fillFromModel($contract);
    }

    public function fillFromModel(Contract $contract): void
    {
        $this->employee_id           = $contract->employee_id;
        $this->position_id           = $contract->position_id;
        $this->contract_type_id      = $contract->contract_type_id;
        $this->is_active             = (bool) $contract->is_active;

        $this->start_date            = $contract->start_date?->toDateString();
        $this->end_date              = $contract->end_date?->toDateString();
        $this->terminated_at         = $contract->terminated_at?->toDateString();
        $this->termination_reason_id = $contract->termination_reason_id;
        $this->reason_of_termination = $contract->reason_of_termination;
        $this->weekly_hours          = (int) $contract->weekly_hours;

        $this->currency        = (int) $contract->currency;
        $this->salary          = $contract->salary;
        $this->salary_per      = $contract->salary_per;
        $this->work_days_ar    = $contract->work_days_ar;
        $this->work_days_en    = $contract->work_days_en;
        $this->work_hours_from = $contract->work_hours_from;
        $this->work_hours_to   = $contract->work_hours_to;
    }

    // ────────────────────────────────────────────────
    // Validation
    // ────────────────────────────────────────────────

    public function validateIdentity(): array
    {
        return $this->validate([
            'employee_id'      => ['required', 'exists:employees,id'],
            'position_id'      => ['required', 'exists:positions,id', $this->positionCapacityRule()],
            'contract_type_id' => ['required', 'exists:contract_types,id'],
            'is_active'        => ['required', 'boolean'],
        ]);
    }

    public function validateTerms(): array
    {
        $maxEndDate = $this->start_date
            ? Carbon::parse($this->start_date)->addYear()->toDateString()
            : null;

        return $this->validate([
            'start_date'             => ['required', 'date'],
            'end_date'               => array_filter([
                'required',
                'date',
                'after_or_equal:start_date',
                $maxEndDate ? "before_or_equal:{$maxEndDate}" : null,
            ]),
            'terminated_at'          => ['nullable', 'date', 'after_or_equal:start_date'],
            'termination_reason_id'  => ['nullable', 'exists:termination_reasons,id'],
            'reason_of_termination'  => ['nullable', 'string'],
            'weekly_hours'           => ['required', 'integer', 'min:1'],
        ]);
    }

    public function validatePay(): array
    {
        return $this->validate([
            'currency'   => ['required', 'integer', Rule::in([0, 1, 2, 3])],
            'salary'     => ['required', 'numeric', 'min:0'],
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
                $this->contract = Contract::create($this->buildPayload());
            }

            return $this->contract;
        });
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
            'employee_id'      => $this->employee_id,
            'position_id'      => $this->position_id,
            'contract_type_id' => $this->contract_type_id,
            'is_active'        => (bool) $this->is_active,
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
            'start_date'            => $this->start_date,
            'end_date'              => $this->end_date,
            'terminated_at'         => $this->terminated_at ?? null,
            'termination_reason_id' => $this->termination_reason_id ?? null,
            'reason_of_termination' => $this->reason_of_termination ?? null,
            'weekly_hours'          => (int) $this->weekly_hours,
            'work_days_ar'          => $this->work_days_ar,
            'work_days_en'          => $this->work_days_en,
            'work_hours_from'       => $this->work_hours_from,
            'work_hours_to'         => $this->work_hours_to,
            // Automatically deactivate when a termination is recorded
            'is_active'             => $isTerminated ? false : $this->is_active,
        ]);
    }

    public function savePay(): void
    {
        $this->validatePay();

        $this->contract?->update([
            'currency'   => (int) $this->currency,
            'salary'     => $this->salary,
            'salary_per' => (string) $this->salary_per,
        ]);
    }

    // ────────────────────────────────────────────────
    // Private helpers
    // ────────────────────────────────────────────────

    private function buildPayload(): array
    {
        return [
            'employee_id'           => $this->employee_id,
            'position_id'           => $this->position_id,
            'contract_type_id'      => $this->contract_type_id,
            'is_active'             => (bool) $this->is_active,
            'start_date'            => $this->start_date,
            'end_date'              => $this->end_date,
            'terminated_at'         => $this->terminated_at ?? null,
            'termination_reason_id' => $this->termination_reason_id,
            'reason_of_termination' => $this->reason_of_termination ?? null,
            'weekly_hours'          => (int) $this->weekly_hours,
            'work_days_ar'          => $this->work_days_ar,
            'work_days_en'          => $this->work_days_en,
            'work_hours_from'       => $this->work_hours_from,
            'work_hours_to'         => $this->work_hours_to,
            'currency'              => (int) $this->currency,
            'salary'                => $this->salary,
            'salary_per'            => (string) $this->salary_per,
        ];
    }

    /**
     * Closure-based validation rule that enforces position capacity,
     * skipping the check when the position has not changed on an existing contract.
     */
    private function positionCapacityRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            $isUnchanged = $this->contract && $this->contract->position_id === (int) $value;

            if ($isUnchanged) {
                return;
            }

            $position = Position::find($value);

            if ($position && ! $position->canAssign()) {
                $fail(__('This position exceeded the number of max employees.'));
            }
        };
    }


 




}
