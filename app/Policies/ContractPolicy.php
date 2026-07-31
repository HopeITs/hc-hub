<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function view(User $user, Contract $contract): bool
    {
        return $user->hasActivePermission('contracts.view') && $this->isInActiveCity($user, $contract);
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->hasActivePermission('contracts.update') && $this->isInActiveCity($user, $contract);
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->hasActivePermission('contracts.delete') && $this->isInActiveCity($user, $contract);
    }

    public function export(User $user, Contract $contract): bool
    {
        return $user->hasActivePermission('contracts.export') && $this->isInActiveCity($user, $contract);
    }

    private function isInActiveCity(User $user, Contract $contract): bool
    {
        $activeCityId = $user->activeCityId();

        if ($activeCityId === null) {
            return true;
        }

        return $contract->employee()
            ->where('city_id', $activeCityId)
            ->exists();
    }
}
