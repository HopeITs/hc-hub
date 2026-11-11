<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\Badge;
use Modules\Hr\Models\Employee;

/**
 * Badges (warnings, rewards, salary increases) follow the employee they are
 * about: see Badge::constrainEmployees for who covers whom.
 */
class BadgePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('badges.view');
    }

    public function view(User $user, Badge $badge): bool
    {
        return $this->canAccessEmployee($user, $badge->employee, 'badges.view');
    }

    public function viewForEmployee(User $user, Employee $employee): bool
    {
        return $this->canAccessEmployee($user, $employee, 'badges.view');
    }

    public function create(User $user, Employee $employee): bool
    {
        return $this->canAccessEmployee($user, $employee, 'badges.create');
    }

    public function update(User $user, Badge $badge): bool
    {
        return $this->canAccessEmployee($user, $badge->employee, 'badges.create');
    }

    public function delete(User $user, Badge $badge): bool
    {
        return $this->canAccessEmployee($user, $badge->employee, 'badges.delete');
    }

    private function canAccessEmployee(User $user, ?Employee $employee, string $permission): bool
    {
        if (! $employee || ! $user->can($permission)) {
            return false;
        }

        return Badge::coversEmployee($user, $employee);
    }
}
