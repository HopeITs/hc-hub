<?php

namespace App\Livewire\Dashboard;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Livewire\Component;

class UpcomingLeavesCard extends Component
{
    public $upcomingLeaves;

    public function mount()
    {
        $user = auth()->user()->load('profile');

        $role = $user->myRole();
        $query = LeaveRequest::whereDate('start_at', '>=', Carbon::now());
        if ($role === 'HR-SYR') {
            $query->whereIn('status', [LeaveRequestStatus::Accepted, LeaveRequestStatus::Pending]);
        } elseif (str_starts_with($role, 'HR-')) {

            $query->whereIn('status', [LeaveRequestStatus::Accepted, LeaveRequestStatus::Pending])
                ->whereHas('leaveBalance.contract.employee.city', function ($q) use ($role) {
                    $cityCode = substr($role, 3);
                    $q->where('slug', $cityCode);

                });
        } else {
            $query->where('status', LeaveRequestStatus::Accepted)
                ->with(['leaveBalance.contract.position' => function ($query) use ($user) {
                    return $query->where('department_id', $user->profile->latestActiveContract()->position->department_id)
                        ->orWhere('center_id', $user->profile->latestActiveContract()->position->center_id);
                }, 'leaveBalance.contract.employee',
                ]);
        }

        $this->upcomingLeaves = $query->orderBy('start_at')->get();

    }

    public function render()
    {

        return view('livewire.dashboard.upcoming-leaves-card', [
        ]);
    }
}
