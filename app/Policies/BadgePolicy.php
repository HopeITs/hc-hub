<?php

namespace App\Policies;

use App\Models\Badge;
use App\Models\Employee;
use App\Models\User;

class BadgePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->activeRoleIs('HR', 'super-admin');
    }

    public function view(User $user, Badge $badge): bool
    {
        return $this->canManageEmployee($user, $badge->employee);
    }

    public function create(User $user, Employee $employee): bool
    {
        return $this->canManageEmployee($user, $employee);
    }

    public function delete(User $user, Badge $badge): bool
    {
        return $this->canManageEmployee($user, $badge->employee);
    }

    private function canManageEmployee(User $user, ?Employee $employee): bool
    {
        if (! $employee || ! $this->viewAny($user)) {
            return false;
        }

        $cityId = $user->activeCityId();

        return $cityId === null || $employee->city_id === $cityId;
    }
}
