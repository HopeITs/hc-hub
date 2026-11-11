<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Support\Leave\HolidayProjects;

/**
 * Holidays are set by national HR (any kind, any scope) and by program
 * managers (official holidays and one-off grants for their own projects,
 * H-6). Every rule lives in HolidayProjects, so the picker, the list and
 * these checks cannot drift.
 */
class HolidayPolicy
{
    public function __construct(private readonly HolidayProjects $projects) {}

    public function viewAny(User $user): bool
    {
        return $this->projects->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->projects->canManage($user);
    }

    public function update(User $user, Holiday $holiday): bool
    {
        return $this->projects->canChange($user, $holiday);
    }

    public function delete(User $user, Holiday $holiday): bool
    {
        return $this->projects->canChange($user, $holiday);
    }
}
