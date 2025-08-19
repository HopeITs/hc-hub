<?php

namespace App\Livewire\Forms;

use App\Enums\SalaryPer;
use App\Models\Contract;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ContractForm extends Form
{
    public ?Contract $contract = null;

    // identity
    public $employee_id;
    public $position_id;
    public $contract_type_id;
    public $department_id;
    public $job_level_id;
    public $is_active = true;

    // terms
    public $start_date;
    public $end_date;
    public $terminated_at;
    public $reason_of_termination;
    public $yearly_leave_days = 12;
    public $weekly_hours = 40;

    //pay
    public $currency = 1;       // 0=Unknown, 1=SYP, 2=USD, 3=EUR
    public $salary;
    public $salary_per = SalaryPer::Month; // 0..3


    public $work_days  = ['day'  => ['from' => 'mon',  'to' => 'fri']];
    public $work_hours = ['hour' => ['from' => '09:00','to' => '17:00']];


    public function setDefaults(): void
    {
        $this->is_active         = true;
        $this->yearly_leave_days = 12;
        $this->weekly_hours      = 40;
        $this->currency          = 1; // SYP (adjust if you prefer 0=Unknown)
        $this->salary_per        = SalaryPer::Month;
    }


    public function setContract(Contract $contract): void
    {
        $this->contract            = $contract;

        $this->employee_id         = $contract->employee_id;
        $this->position_id         = $contract->position_id;
        $this->contract_type_id    = $contract->contract_type_id;
        $this->department_id       = $contract->department_id;
        $this->job_level_id        = $contract->job_level_id;
        $this->is_active           = (bool) $contract->is_active;

        $this->start_date          = optional($contract->start_date)?->toDateString();
        $this->end_date            = optional($contract->end_date)?->toDateString();
        $this->terminated_at       = optional($contract->terminated_at)?->toDateString();
        $this->reason_of_termination = $contract->reason_of_termination;
        $this->yearly_leave_days   = $contract->yearly_leave_days;
        $this->weekly_hours        = $contract->weekly_hours;

        $this->currency            = (int) $contract->currency;
        $this->salary              = $contract->salary;
        $this->salary_per          = (int) $contract->salary_per;

        $this->work_days           = $contract->work_days ?? $this->work_days;
        $this->work_hours          = $contract->work_hours ?? $this->work_hours;
    }

    public function validateIdentity(): array
    {
        return $this->validate([
            'employee_id'      => ['required', 'exists:employees,id'],
            'position_id'      => ['required', 'exists:positions,id'],
            'contract_type_id' => ['required', 'exists:contract_types,id'],
            'department_id'    => ['required', 'exists:departments,id'],
            'job_level_id'     => ['required', 'exists:job_levels,id'],
            'is_active'        => ['required', 'boolean'],
        ]);
    }

    public function validateTerms(): array
    {
        return $this->validate([
            'start_date'            => ['required', 'date'],
            'end_date'              => ['required', 'date', 'after_or_equal:start_date'],
            'terminated_at'         => ['nullable', 'date', 'after_or_equal:start_date'],
            'reason_of_termination' => ['nullable', 'string'],
            'yearly_leave_days'     => ['required', 'integer', 'min:0'],
            'weekly_hours'          => ['required', 'integer', 'min:0'],
        ]);
    }

    public function validateCompensation(): array
    {
        return $this->validate([
            'currency'   => ['required', 'integer', Rule::in([0,1,2,3])],
            'salary'     => ['required', 'numeric', 'min:0'],
            'salary_per' => ['required', 'integer', Rule::in([0,1,2,3])],
        ]);
    }

    public function save(): Contract
    {
        // Full validation across tabs
        $this->validateIdentity();
        $this->validateTerms();
        $this->validateCompensation();

        $payload = [
            'employee_id'         => $this->employee_id,
            'position_id'         => $this->position_id,
            'contract_type_id'    => $this->contract_type_id,
            'department_id'       => $this->department_id,
            'job_level_id'        => $this->job_level_id,
            'start_date'          => $this->start_date,
            'end_date'            => $this->end_date,
            'terminated_at'       => $this->terminated_at,
            'reason_of_termination' => $this->reason_of_termination,
            'yearly_leave_days'   => $this->yearly_leave_days,
            'currency'            => $this->currency,
            'salary'              => $this->salary,
            'salary_per'          => $this->salary_per,
            'weekly_hours'        => $this->weekly_hours,
            'work_days'           => $this->work_days,
            'work_hours'          => $this->work_hours,
            'is_active'           => (bool) $this->is_active,
        ];

        if ($this->contract) {
            $this->contract->update($payload);
        } else {
            $this->contract = Contract::create($payload);
        }

        return $this->contract;
    }

    public function saveIdentity(): void
    {
        $this->validateIdentity();
        if ($this->contract) {
            $this->contract->update($this->only([
                'employee_id','position_id','contract_type_id','department_id','job_level_id','is_active'
            ]));
        }
    }

    public function saveTerms(): void
    {
        $this->validateTerms();
        if ($this->contract) {
            $this->contract->update($this->only([
                'start_date','end_date','terminated_at','reason_of_termination','yearly_leave_days','weekly_hours','work_days','work_hours'
            ]));
        }
    }

    public function saveCompensation(): void
    {
        $this->validateCompensation();
        if ($this->contract) {
            $this->contract->update($this->only([
                'currency','salary','salary_per'
            ]));
        }
    }
}
