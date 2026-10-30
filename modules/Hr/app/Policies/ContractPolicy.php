<?php

namespace Modules\Hr\Policies;

use Modules\Hr\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function view(User $user, Contract $contract): bool
    {
        return $user->hasActivePermission('hr.contracts.view') && $this->isVisibleTo($user, $contract);
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->hasActivePermission('hr.contracts.update') && $this->isVisibleTo($user, $contract);
    }

    public function manageAutoRenew(User $user, Contract $contract): bool
    {
        return $user->activeRoleIs('HR')
            && $user->hasActivePermission('hr.contracts.update')
            && $this->isVisibleTo($user, $contract);
    }

    /**
     * A renewal creates a contract, so it is gated on `hr.contracts.create` rather
     * than on update. A terminated or already-deactivated contract has no term
     * left to carry forward and is never renewable — the successor would have
     * to be created from scratch.
     */
    public function renew(User $user, Contract $contract): bool
    {
        return $user->hasActivePermission('hr.contracts.create')
            && $contract->is_active
            && $contract->terminated_at === null
            && $this->isVisibleTo($user, $contract);
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->hasActivePermission('hr.contracts.delete') && $this->isVisibleTo($user, $contract);
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
        return $user->hasActivePermission('hr.contracts.export')
            && $user->canViewSalary()
            && $this->isVisibleTo($user, $contract);
    }

    /**
     * Every ability starts from visibility: a role may only act on contracts it
     * can see, through the same scope ContractsTable lists with — city for
     * regional HR, direct/technical reports for a manager.
     *
     * @see Contract::scopeVisibleTo()
     */
    private function isVisibleTo(User $user, Contract $contract): bool
    {
        return Contract::query()->whereKey($contract->getKey())->visibleTo($user)->exists();
    }
}
