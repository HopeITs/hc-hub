<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function view(User $user, Position $position): bool
    {
        return $user->hasActivePermission('positions.view') && $this->isInActiveCity($user, $position);
    }

    public function create(User $user, ?int $cityId): bool
    {
        return $user->hasActivePermission('positions.create')
            && ($user->activeCityId() === null || $user->activeCityId() === $cityId);
    }

    public function update(User $user, Position $position): bool
    {
        return $user->hasActivePermission('positions.update') && $this->isInActiveCity($user, $position);
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->hasActivePermission('positions.delete') && $this->isInActiveCity($user, $position);
    }

    private function isInActiveCity(User $user, Position $position): bool
    {
        $activeCityId = $user->activeCityId();

        return $activeCityId === null
            || Position::query()->inCity($activeCityId)->whereKey($position)->exists();
    }
}
