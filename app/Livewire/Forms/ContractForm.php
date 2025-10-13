<?php

namespace App\Livewire\Forms;

use App\Models\Contract;
use App\Enums\SalaryPer;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ContractForm extends Form
{
    public ?Contract $contract = null;

    // ── Identity (current schema)
    public ?int $employee_id = null;
    public ?int $position_id = null;
    public ?int $contract_type_id = null;
    public bool $is_active = true;

    // ── Terms
    public ?string $start_date = null;
    public ?string $end_date = null;
    public ?string $terminated_at = null; // nullable
    public ?string $reason_of_termination = null; // nullable
    public int $yearly_leave_days = 12;
    public int $weekly_hours = 40;

    // ── Pay
    public int $currency = 1;
    public $salary;
    public int $salary_per = 1;

    // ── Schedule
    public array $work_days = ['day' => ['from' => 'mon', 'to' => 'fri']];
    public array $work_hours = ['hour' => ['from' => '09:00', 'to' => '17:00']];

    private function toInt($value): int
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }
        return (string) $value;
    }

    private function defaultSalaryPer(): int
    {
        if (enum_exists(SalaryPer::class)) {
            return (int) SalaryPer::Month->value;
        }

        return defined(SalaryPer::class . '::Month') ? (int) constant(SalaryPer::class . '::Month') : 1; // safe fallback
    }

    private function allowedSalaryPerValues(): array
    {
        if (enum_exists(SalaryPer::class) && method_exists(SalaryPer::class, 'cases')) {
            return array_map(fn($case) => $case instanceof \BackedEnum ? (int) $case->value : (int) $case->name, SalaryPer::cases());
        }

        $keys = ['Unknown', 'Month', 'Hour', 'Session'];
        $vals = [];
        foreach ($keys as $k) {
            $const = SalaryPer::class . '::' . $k;
            if (defined($const)) {
                $vals[] = (int) constant($const);
            }
        }

        return $vals ?: [0, 1, 2, 3];
    }

    private function nullIfEmpty($value)
    {
        return blank($value) ? null : $value;
    }

    // ─────────────────────────────────────────────────────────

    public function setDefaults(): void
    {
        $this->is_active = true;
        $this->yearly_leave_days = 12;
        $this->weekly_hours = 40;
        $this->currency = 1; // SYP
        $this->salary_per = $this->defaultSalaryPer();
        $this->terminated_at = null;
        $this->reason_of_termination = null;
    }

    public function setContract(Contract $contract): void
    {
        $this->contract = $contract;
        $this->fillFromModel($contract);
    }

    public function fillFromModel(Contract $c): void
    {
        $this->employee_id = $c->employee_id;
        $this->position_id = $c->position_id;
        $this->contract_type_id = $c->contract_type_id;

        $this->is_active = (bool) $c->is_active;

        $this->start_date = optional($c->start_date)?->toDateString();
        $this->end_date = optional($c->end_date)?->toDateString();
        $this->terminated_at = optional($c->terminated_at)?->toDateString();
        $this->reason_of_termination = $c->reason_of_termination;
        $this->yearly_leave_days = (int) $c->yearly_leave_days;
        $this->weekly_hours = (int) $c->weekly_hours;

        $this->currency = (int) $c->currency;
        $this->salary = $c->salary;
        $this->salary_per = $this->toInt($c->salary_per);

        // Fix for MariaDB/longtext JSON
        $this->work_days = is_string($c->work_days) ? json_decode($c->work_days, true) ?? [] : $c->work_days ?? $this->work_days;

        $this->work_hours = is_string($c->work_hours) ? json_decode($c->work_hours, true) ?? [] : $c->work_hours ?? $this->work_hours;
    }

    // ── STEP 1: Identity
    public function validateIdentity(): array
    {
        return $this->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'contract_type_id' => ['required', 'exists:contract_types,id'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    // ── STEP 2: Terms
    public function validateTerms(): array
    {
        return $this->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'terminated_at' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason_of_termination' => ['nullable', 'string'],
            'yearly_leave_days' => ['required', 'integer', 'min:0'],
            'weekly_hours' => ['required', 'integer', 'min:0'],
        ]);
    }

    // ── STEP 3: Pay
    public function validatePay(): array
    {
        return $this->validate([
            'currency' => ['required', 'integer', Rule::in([0, 1, 2, 3])],
            'salary' => ['required', 'numeric', 'min:0'],
            'salary_per' => ['required', 'integer', Rule::in($this->allowedSalaryPerValues())],
        ]);
    }

    // ── Build payload for create/update
    private function buildPayload(): array
    {
        return [
            // Identity
            'employee_id' => $this->employee_id,
            'position_id' => $this->position_id,
            'contract_type_id' => $this->contract_type_id,
            'is_active' => (bool) $this->is_active,

            // Terms
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'terminated_at' => $this->nullIfEmpty($this->terminated_at),
            'reason_of_termination' => $this->nullIfEmpty($this->reason_of_termination),
            'yearly_leave_days' => (int) $this->yearly_leave_days,
            'weekly_hours' => (int) $this->weekly_hours,
            'work_days' => $this->work_days,
            'work_hours' => $this->work_hours,

            // Pay
            'currency' => (int) $this->currency,
            'salary' => $this->salary,
            'salary_per' => (string) $this->salary_per,
        ];
    }

    // ── Orchestrated save (used by submit)
    public function save(): Contract
    {
        $this->validateIdentity();
        $this->validateTerms();
        $this->validatePay();

        $payload = $this->buildPayload();

        if ($this->contract) {
            $this->contract->update($payload);
        } else {
            $this->contract = Contract::create($payload);
        }


        return $this->contract;
    }

    public function update(Contract $contract): bool
    {
        return $contract->update($this->buildPayload());
    }

    // ── Granular saves (if used elsewhere)
    public function saveIdentity(): void
    {
        $this->validateIdentity();

        if ($this->contract) {
            $this->contract->update([
                'employee_id' => $this->employee_id,
                'position_id' => $this->position_id,
                'contract_type_id' => $this->contract_type_id,
                'is_active' => (bool) $this->is_active,
            ]);
        }
    }

    public function saveTerms(): void
    {
        $this->validateTerms();

        if ($this->contract) {
            $this->contract->update([
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'terminated_at' => $this->nullIfEmpty($this->terminated_at),
                'reason_of_termination' => $this->nullIfEmpty($this->reason_of_termination),
                'yearly_leave_days' => (int) $this->yearly_leave_days,
                'weekly_hours' => (int) $this->weekly_hours,
                'work_days' => $this->work_days,
                'work_hours' => $this->work_hours,
            ]);
        }
    }

    public function savePay(): void
    {
        $this->validatePay();

        if ($this->contract) {
            $this->contract->update([
                'currency' => (int) $this->currency,
                'salary' => $this->salary,
                'salary_per' => (string) $this->salary_per,
            ]);
        }
    }
}
