<?php

namespace App\Livewire\Forms;

use App\Enums\LeaveTypeCases;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LeaveRequestForm extends Form
{
    public ?LeaveRequest $leaveRequest = null;


    #[Validate('required|exists:leave_balances,id')]
    public $leave_balance_id = null;

    public $id = null;

    // For hour-based requests
    public $leave_date = null;
    public $start_hour = null;
    public $end_hour = null;

    // For day-based requests
    public $start_at = '';
    public $end_at = '';

    #[Validate('required|string|min:3')]
    public $reason = '';

    #[Validate('nullable|exists:employees,id')]
    public $alternative_employee = null;

    #[Validate('nullable|exists:employees,id')]
    public $approved_by = null;

    public $approved_at = null;
    public $status = 0; // 0 = pending
    public $type = 'day'; // matches DB column enum ['day', 'hour']

    /**
     * Dynamic validation rules depending on leave type
     */
    public function rules()
    {
        $employee = auth()->user()->profile;
        $maxHours = ($employee->latestActiveContract()->weekly_hours / 5) / 2;

        $rules = [
            'type' => ['required', Rule::in(['day', 'hour'])],
            'reason' => ['required', 'string', 'min:3'],
            'leave_balance_id' => ['required', 'exists:leave_balances,id'],
        ];

        // Fetch center open and close times
        $center = $employee->latestActiveContract()->position->center;
        $centerOpenTime = Carbon::parse($center->open_at ?? '09:00');
        $centerCloseTime = Carbon::parse($center->close_at ?? '16:30');

        if ($this->type === 'hour') {
            $rules['start_hour'] = ['required', 'date_format:H:i', function ($attribute, $value, $fail) use ($centerOpenTime, $centerCloseTime) {
                $start = Carbon::createFromFormat('H:i', $value);

                // Check if start hour is within center's working hours
                if ($start->lessThan($centerOpenTime)) {
                    $fail(__('Start hour must be within the center\'s working hours.'));
                }
            }];

            $rules['end_hour'] = [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($centerOpenTime, $centerCloseTime,$maxHours) {
                    if (!$this->start_hour || !$this->end_hour) return;

                    $start = Carbon::createFromFormat('H:i', $this->start_hour);
                    $end = Carbon::createFromFormat('H:i', $value);

                    // Check if end hour is within center's working hours
                    if ($end->greaterThan($centerCloseTime)) {
                        $fail(__('End hour must be within the center\'s working hours.'));
                        return;
                    }

                    // Check that end hour is after start hour
                    if ($end->lessThanOrEqualTo($start)) {
                        $fail(__('End hour must be after start hour.'));
                        return;
                    }

                    $duration = $start->DiffInHours($end);

                    if ($duration > $maxHours) {
                        $fail(__('Duration must not exceed :max hours.', ['max' => $maxHours]));
                    }
                },
            ];

            $rules['leave_date'] = ['required', 'after_or_equal:today'];
        } else {
            $rules['start_at'] = ['required', 'after_or_equal:today'];
            $rules['end_at'] = ['required', 'after_or_equal:start_at'];
        }

        return $rules;
    }


    /**
     * Set form state from an existing leave request
     */
    public function setLeaveRequest(LeaveRequest $leaveRequest): void
    {

        $this->leaveRequest = $leaveRequest;
        $this->fill($leaveRequest->only([
            'id',
            'leave_balance_id',
            'start_at',
            'end_at',
            'reason',
            'alternative_employee',
            'approved_by',
            'approved_at',
            'status',
            'type',
        ]));
        // Handle hour-based requests
        if ($leaveRequest->type === 'hour') {
            $this->leave_date = Carbon::parse($leaveRequest->start_at)->toDateString();
            $this->start_hour = Carbon::parse($leaveRequest->start_at)->format('H:i');
            $this->end_hour = Carbon::parse($leaveRequest->end_at)->format('H:i');
        }else{
            $this->start_at = Carbon::parse($leaveRequest->start_at)->toDateString();
            $this->end_at = Carbon::parse($leaveRequest->end_at)->toDateString();
        }
    }

    /**
     * Store new leave request
     */
    public function store(): void
    {

        $this->validate();


        if($this->type === 'hour') {
            $start_at = $this->start_hour .' '. $this->leave_date;
            $this->start_at = Carbon::createFromFormat('H:i Y-m-d', $start_at);

            $end_at = $this->end_hour .' '. $this->leave_date;
            $this->end_at = Carbon::createFromFormat('H:i Y-m-d', $end_at);
        }


        $leaveBalance = LeaveBalance::find($this->leave_balance_id);

        // --- Handle Annual Leave Accrual ---
        if ($leaveBalance->isAnnual()) {
            $requestedHours = $this->calculateRequestedHours();

            if (!$leaveBalance->canRequestAnnualLeave($requestedHours)) {
                throw ValidationException::withMessages([
                    'leave_balance_id' => __('You have exceeded your accrued annual leave allowance.'),
                ]);
            }
        }

        LeaveRequest::create([
            'leave_balance_id' => $this->leave_balance_id,
            'type' => $this->type,
            'start_at' => $this->start_at ?? $this->combineDateTime($this->leave_date, $this->start_hour),
            'end_at' => $this->end_at ?? $this->combineDateTime($this->leave_date, $this->end_hour),
            'reason' => $this->reason,
            'alternative_employee' => $this->alternative_employee,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'status' => $this->status,
        ]);

        $this->resetForm();
    }

    /**
     * Update an existing leave request
     */
    public function update(): void
    {

        $this->validate();
        $leaveBalance = $this->leaveRequest->leaveBalance;

        if ($leaveBalance->isAnnual()) {
            $requestedHours = $this->calculateRequestedHours();
            if (!$leaveBalance->canRequestAnnualLeave($requestedHours)) {
                throw ValidationException::withMessages([
                    'leave_balance_id' => __('You have exceeded your accrued annual leave allowance.'),
                ]);
            }
        }
        if($this->type === 'hour') {

            $start_at = $this->start_hour .' '. $this->leave_date;
            $this->start_at = Carbon::createFromFormat('H:i Y-m-d', $start_at);

            $end_at = $this->end_hour .' '. $this->leave_date;
            $this->end_at = Carbon::createFromFormat('H:i Y-m-d', $end_at);
        }

        $this->leaveRequest->update($this->all());

        $this->resetForm();
    }

    /**
     * Combine date + time safely
     */
    protected function combineDateTime(?string $date, ?string $time): ?Carbon
    {
        if (!$date || !$time) return null;
        return Carbon::parse($date . ' ' . $time);
    }

    /**
     * Calculate requested hours depending on leave type
     */
    protected function calculateRequestedHours(): float
    {
        if ($this->type === 'hour') {
            $start = $this->combineDateTime($this->leave_date, $this->start_hour);
            $end = $this->combineDateTime($this->leave_date, $this->end_hour);
            return $start->floatDiffInHours($end);
        }

        if ($this->type === 'day') {
            $start = Carbon::parse($this->start_at)->startOfDay();
            $end = Carbon::parse($this->end_at)->endOfDay();
            $days = $start->diffInDaysFiltered(fn (Carbon $date) => !$date->isWeekend(), $end) + 1;

            return $days * 8; // 8h per day (adjust as needed)
        }

        return 0;
    }



    protected function resetForm(): void
    {
        $this->reset([
            'id',
            'leave_balance_id',
            'leave_date',
            'start_hour',
            'end_hour',
            'start_at',
            'end_at',
            'reason',
            'alternative_employee',
            'approved_by',
            'approved_at',
            'status',
            'type',
        ]);
    }
}
