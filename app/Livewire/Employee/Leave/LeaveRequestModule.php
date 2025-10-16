<?php

namespace App\Livewire\Employee\Leave;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Livewire\Component;

class LeaveRequestModule extends Component
{

    public LeaveBalance $leaveBalance;
    public LeaveRequest $leaveRequest;
    public function render()
    {
        $availableLeaves = auth()->user()->profile->availableLeaves();

        return view('livewire.employee.leave.index',compact('availableLeaves'));
    }
}
