<?php

namespace App\Policies;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->owns($user, $leaveRequest)
            || $this->canManage($user, $leaveRequest);
    }

    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($this->owns($user, $leaveRequest)) {
            return $leaveRequest->status === LeaveRequestStatus::Pending;
        }

        return $this->canManage($user, $leaveRequest);
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($this->owns($user, $leaveRequest)) {
            return $leaveRequest->status === LeaveRequestStatus::Pending;
        }

        return $this->canManage($user, $leaveRequest);
    }

    public function decide(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->status === LeaveRequestStatus::Pending
            && $this->canManage($user, $leaveRequest);
    }

    public function review(User $user, LeaveRequest $leaveRequest): bool
    {
        $role = $user->myRole();

        return $leaveRequest->status !== LeaveRequestStatus::Pending
            && ! $leaveRequest->hr_approved
            && ($role === 'super-admin' || ($role && str_starts_with($role, 'HR-')))
            && $this->canManage($user, $leaveRequest);
    }

    private function owns(User $user, LeaveRequest $leaveRequest): bool
    {
        $employeeId = $leaveRequest->leaveBalance?->contract?->employee_id;

        return $user->employee_id !== null
            && $user->employee_id === $employeeId;
    }

    private function canManage(User $user, LeaveRequest $leaveRequest): bool
    {
        return LeaveRequest::query()
            ->manageableBy($user)
            ->whereKey($leaveRequest->getKey())
            ->exists();
    }
}
