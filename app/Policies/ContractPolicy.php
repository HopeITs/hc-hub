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

    public function manageAutoRenew(User $user, Contract $contract): bool
    {
        return $user->activeRoleIs('HR')
            && $user->hasActivePermission('contracts.update')
            && $this->isInActiveCity($user, $contract);
    }

    /**
     * A renewal creates a contract, so it is gated on `contracts.create` rather
     * than on update. A terminated or already-deactivated contract has no term
     * left to carry forward and is never renewable — the successor would have
     * to be created from scratch.
     */
    public function renew(User $user, Contract $contract): bool
    {
        return $user->hasActivePermission('contracts.create')
            && $contract->is_active
            && $contract->terminated_at === null
            && $this->isInActiveCity($user, $contract);
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->hasActivePermission('contracts.delete') && $this->isInActiveCity($user, $contract);
    }

    /**
     * The exported PDF *is* the contract, pay clause and all, so there is no
     * version of it to hand someone who may not see the figures: a copy with
     * the stipend blanked out is not the document, and one with the stipend in
     * it defeats the restriction. A role barred from pay figures is therefore
     * barred from the export, whatever permissions it otherwise carries.
     *
     * @see User::canViewSalary()
     */
    public function export(User $user, Contract $contract): bool
    {
        return $user->hasActivePermission('contracts.export')
            && $user->canViewSalary()
            && $this->isInActiveCity($user, $contract);
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
