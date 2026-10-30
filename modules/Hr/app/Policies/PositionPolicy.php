<?php

namespace Modules\Hr\Policies;

use Modules\Hr\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function view(User $user, Position $position): bool
    {
        return $user->hasActivePermission('hr.positions.view') && $this->isInActiveCity($user, $position);
    }

    public function create(User $user, ?int $cityId): bool
    {
        return $user->hasActivePermission('hr.positions.create')
            && $user->canAccessCity($cityId);
    }

    public function update(User $user, Position $position): bool
    {
        return $user->hasActivePermission('hr.positions.update') && $this->isInActiveCity($user, $position);
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->hasActivePermission('hr.positions.delete') && $this->isInActiveCity($user, $position);
    }

    private function isInActiveCity(User $user, Position $position): bool
    {
        $activeCityIds = $user->activeCityIds();

        return $activeCityIds === null
            || Position::query()->inCity($activeCityIds)->whereKey($position)->exists();
    }
}
