<?php

namespace App\Livewire\Forms;

use App\Models\LeaveRequest;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LeaveRequestForm extends Form
{

    public ?LeaveRequest $leaveRequest = null;
    #[Validate('required|exists:leave_balances,id')]
    public $leave_balance_id = null;

    #[Validate('required')]
    public $start_date = '';

    #[Validate('required')]
    public $end_date = '';

    #[Validate('required')]
    public $reason = '';

    #[Validate('exists:employees,id')]
    public $alternative_employee = '';
    #[Validate('exists:employees,id')]
    public $approved_by = null;
    public $approved_at = null;
    public $status = 'pending';
    public $unit;


    public function setLeaveRequest(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
        $this->leave_balance_id = $leaveRequest->leave_balance_id;
        $this->start_date = $leaveRequest->start_date;
        $this->end_date = $leaveRequest->end_date;
        $this->reason = $leaveRequest->reason;
        $this->alternative_employee = $leaveRequest->alternative_employee;
        $this->approved_by = $leaveRequest->approved_by;
        $this->approved_at = $leaveRequest->approved_at;
        $this->status = $leaveRequest->status;


    }

    public function store()
    {
        $this->validate();
        LeaveRequest::create($this->all());
        $this->reset();
    }
}
