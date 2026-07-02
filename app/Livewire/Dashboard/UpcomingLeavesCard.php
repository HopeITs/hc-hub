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
            $position = $user->profile?->latestActiveContract()?->position;

            if (! $position) {
                $this->upcomingLeaves = collect();

                return;
            }

            $query->where('status', LeaveRequestStatus::Accepted)
                ->whereHas('leaveBalance.contract.position', function ($query) use ($position) {
                    $query->where('department_id', $position->department_id)
                        ->orWhere('center_id', $position->center_id);
                })
                ->with(['leaveBalance.contract.position', 'leaveBalance.contract.employee']);
        }

        $this->upcomingLeaves = $query->orderBy('start_at')->get();

    }

    public function render()
    {

        return view('livewire.dashboard.upcoming-leaves-card', [
        ]);
    }
}
