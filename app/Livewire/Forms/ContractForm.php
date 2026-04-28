<?php

namespace App\Livewire\Forms;

use App\Models\Contract;
use App\Enums\SalaryPer;
use App\Models\ContractType;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
    public  $start_date ;
    public  $end_date  ;
    public  $terminated_at = null; // nullable
    public ?string $reason_of_termination = null; // nullable

    public int $weekly_hours = 40;

    // ── Pay
    public int $currency = 1;
    public $salary;
    public int $salary_per = 1;

    // ── Schedule
    public  $work_days_ar ;
    public  $work_days_en ;
    public  $work_hours_from = '09:00' ;
    public  $work_hours_to = '17:00';
    public function updatedContractTypeId($value): void
    {
        if (!$value) return;

        $nextMonthStart = Carbon::now()->addMonthNoOverflow()->startOfMonth()->toDateString();
        // All types share this same start date rule
        $this->start_date = $nextMonthStart;
        if($value === 3 || $value === 4){ //trial contract
            $this->end_date = Carbon::parse($nextMonthStart)->addMonthsNoOverflow(3)->toDateString();
        }else{
            $this->end_date = Carbon::now()->endOfYear()->toDateString();
        }

    }


    private function defaultSalaryPer(): int
    {
        if (enum_exists(SalaryPer::class)) {
            return (int) SalaryPer::Month->value;
        }

        return defined(SalaryPer::class . '::Month') ? (int)constant(SalaryPer::class . '::Month') : 1; // safe fallback
    }

    private function allowedSalaryPerValues(): array
    {
        if (enum_exists(SalaryPer::class) && method_exists(SalaryPer::class, 'cases')) {
            return array_map(fn($case) => $case instanceof \BackedEnum ? (int)$case->value : (int)$case->name, SalaryPer::cases());
        }

        $keys = ['Unknown', 'Month', 'Hour', 'Session'];
        $vals = [];
        foreach ($keys as $k) {
            $const = SalaryPer::class . '::' . $k;
            if (defined($const)) {
                $vals[] = (int)constant($const);
            }
        }

        return $vals ?: [0, 1, 2, 3];
    }

    private function nullIfEmpty($value)
    {
        return blank($value) ? null : $value;
    }


    public function setDefaults(): void
    {
        $this->is_active = true;
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

        $this->is_active = (bool)$c->is_active;

        $this->start_date = optional($c->start_date)?->toDateString();
        $this->end_date = optional($c->end_date)?->toDateString();
        $this->terminated_at = optional($c->terminated_at)?->toDateString();
        $this->reason_of_termination = $c->reason_of_termination;
        $this->weekly_hours = (int)$c->weekly_hours;

        $this->currency = (int)$c->currency;
        $this->salary = $c->salary;
        $this->salary_per = (int) $c->salary_per->value ?? $c->salary_per;
        $this->work_days_ar =  $c->work_days_ar;
        $this->work_days_en =  $c->work_days_en;
        $this->work_hours_from =  $c->work_hours_from;
        $this->work_hours_to =  $c->work_hours_to;

    }

    public function validateIdentity(): array
    {
        return $this->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'position_id' => [
                'required',
                'exists:positions,id',
                function ($attribute, $value, $fail) {
                    if (isset($this->contract)) {
                        
                        if ($this->contract->position_id !== $value) {
                            $pos = Position::find($value);
                            if ($pos && !$pos->canAssign()) {
                                $fail(__('This position exceeded the number of max employees.'));
                            }
                        }
                    } else {
                        $pos = Position::find($value);
                        if ($pos && !$pos->canAssign()) {
                            $fail(__('This position exceeded the number of max employees.'));
                        }
                    }

                },
            ],
            'contract_type_id' => ['required', 'exists:contract_types,id'],
            'is_active' => ['required', 'boolean'],
        ]);
    }


    public function validateTerms(): array
    {
        $limit = $this->start_date
            ? Carbon::parse($this->start_date)->addYear()->toDateString()
            : null;

        return $this->validate([
            'start_date' => ['required', 'date'],
            'end_date' => array_filter([
                'required',
                'date',
                'after_or_equal:start_date',
                $limit ? "before_or_equal:{$limit}" : null,
            ]),
            'terminated_at' => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason_of_termination' => ['nullable', 'string'],
            'weekly_hours' => ['required', 'integer', 'min:0'],
        ]);
    }


    public function validatePay(): array
    {
        return $this->validate([
            'currency' => ['required', 'integer', Rule::in([0, 1, 2, 3])],
            'salary' => ['required', 'numeric', 'min:0'],
            'salary_per' => ['required', 'integer', Rule::in($this->allowedSalaryPerValues())],
        ]);
    }

    private function buildPayload(): array
    {
        return [
            'employee_id' => $this->employee_id,
            'position_id' => $this->position_id,
            'contract_type_id' => $this->contract_type_id,
            'is_active' => (bool)$this->is_active,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'terminated_at' => $this->nullIfEmpty($this->terminated_at),
            'reason_of_termination' => $this->nullIfEmpty($this->reason_of_termination),
            'weekly_hours' => (int)$this->weekly_hours,
            'work_days_ar' => $this->work_days_ar,
            'work_days_en' => $this->work_days_en,
            'work_hours_from' => $this->work_hours_from,
            'work_hours_to' => $this->work_hours_to,
            'currency' => (int)$this->currency,
            'salary' => $this->salary,
            'salary_per' => (string)$this->salary_per,
        ];
    }

    public function save(): Contract
    {
        $this->validateIdentity();
        $this->validateTerms();
        $this->validatePay();

        return DB::transaction(function () {
            $payload = $this->buildPayload();

            if ($this->contract) {
                $this->contract->update($payload);
            } else {
                $this->contract = Contract::create($payload);
            }

            return $this->contract;
        });
    }

    public function update(Contract $contract): bool
    {
        return DB::transaction(function () use ($contract) {
            return $contract->update($this->buildPayload());
        });
    }

    public function saveIdentity(): void
    {
        $this->validateIdentity();

        if ($this->contract) {
            $this->contract->update([
                'employee_id' => $this->employee_id,
                'position_id' => $this->position_id,
                'contract_type_id' => $this->contract_type_id,
                'is_active' => (bool)$this->is_active,
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
                'weekly_hours' => (int)$this->weekly_hours,
                'work_days_ar' => $this->work_days_ar,
                'work_days_en' => $this->work_days_en,
                'work_hours_from' => $this->work_hours_from,
                'work_hours_to' => $this->work_hours_to,
            ]);
        }
    }

    public function savePay(): void
    {
        $this->validatePay();

        if ($this->contract) {
            $this->contract->update([
                'currency' => (int)$this->currency,
                'salary' => $this->salary,
                'salary_per' => (string)$this->salary_per,
            ]);
        }
    }
}
