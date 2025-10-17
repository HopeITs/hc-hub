<?php

namespace App\Livewire\Forms;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LeaveRequestForm extends Form
{

    public ?LeaveRequest $leaveRequest = null;
    #[Validate('required|exists:leave_balances,id')]
    public $leave_balance_id = null;

    public $id = null;

    public $leave_date = null;

    #[Validate('required|after_or_equal:today')]
    public $start_date = '';

    #[Validate('required|after_or_equal:start_date')]
    public $end_date = '';

    public $start_hour = null;
    public $end_hour = null;

    #[Validate('required')]
    public $reason = '';

    #[Validate('exists:employees,id')]
    public $alternative_employee = null;
    #[Validate('nullable|exists:employees,id')]
    public $approved_by = null;
    public $approved_at = null;
    public $status = 'pending';
    public $unit;

    public function rules()
    {
        $employee = auth()->user()->profile;
        $maxHours = ($employee->latestActiveContract()->week_hours / 5) / 2;

        return [
            'unit' => ['required', Rule::in(['day', 'hour'])],
            'start_hour' => [
                Rule::requiredIf(fn() => $this->unit === 'hour'),
                'date_format:H:i',
            ],
            'end_hour' => [
                Rule::requiredIf(fn() => $this->unit === 'hour'),
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($maxHours) {
                    if ($this->unit !== 'hour') {
                        return;
                    }
                    if (!$this->start_hour || !$this->end_hour) {
                        return;
                    }

                    $start = Carbon::createFromFormat('H:i', $this->start_hour);
                    $end = Carbon::createFromFormat('H:i', $this->end_hour);

                    if ($end->lessThanOrEqualTo($start)) {
                        $fail(__('End hour must be after start hour.'));
                        return;
                    }

                    $duration = $end->diffInHours($start) + ($end->diffInMinutes($start) % 60) / 60;
                    if ($duration > $maxHours) {
                        $fail(__('Duration must not exceed :max hours.', ['max' => $maxHours]));
                    }
                },
            ],
            'leave_date' => [
                Rule::requiredIf(fn() => $this->unit === 'hour'),
                'after_or_equal:today'
            ]
        ];
    }

    public function setLeaveRequest(LeaveRequest $leaveRequest)
    {
        $this->leaveRequest = $leaveRequest;
        $this->id = $leaveRequest->id;
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

    public function update()
    {
        $this->validate();
        $this->leaveRequest->update($this->all());

        $this->reset();
    }
}
