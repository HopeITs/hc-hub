<?php

namespace App\Policies;

use App\Models\Center;
use App\Models\User;

/**
 * Centers are city-owned records. A regional HR user could previously open,
 * edit, toggle, delete, and export any center in the country by id — the
 * permission was checked, the city was not.
 */
class CenterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasActivePermission('centers.view');
    }

    public function view(User $user, Center $center): bool
    {
        return $user->hasActivePermission('centers.view') && $this->inActiveCity($user, $center);
    }

    public function create(User $user, ?int $cityId = null): bool
    {
        return $user->hasActivePermission('centers.create')
            && ($cityId === null || $this->cityAllowed($user, $cityId));
    }

    public function update(User $user, Center $center): bool
    {
        return $user->hasActivePermission('centers.update') && $this->inActiveCity($user, $center);
    }

    public function delete(User $user, Center $center): bool
    {
        return $user->hasActivePermission('centers.delete') && $this->inActiveCity($user, $center);
    }

    public function export(User $user): bool
    {
        return $user->hasActivePermission('centers.view');
    }

    private function inActiveCity(User $user, Center $center): bool
    {
        return $this->cityAllowed($user, (int) $center->city_id);
    }

    /** A null active city means the role legitimately spans every city. */
    private function cityAllowed(User $user, int $cityId): bool
    {
        return $user->canAccessCity($cityId);
    }
}
