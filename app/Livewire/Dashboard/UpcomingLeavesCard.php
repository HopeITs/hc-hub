<?php

namespace App\Livewire\Dashboard;

use App\Models\LeaveRequest;
use Livewire\Component;

class UpcomingLeavesCard extends Component
{
    public $upcomingLeaves;

    public function mount(): void
    {
        $user = auth()->user()->load([
            'profile.positions',
            'profile.contracts.position',
        ]);

        $this->upcomingLeaves = LeaveRequest::query()
            ->upcoming()
            ->upcomingVisibleTo($user)
            ->with([
                'leaveBalance.leaveType',
                'leaveBalance.contract.position',
                'leaveBalance.contract.employee',
            ])
            ->orderBy('start_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.upcoming-leaves-card');
    }
}
