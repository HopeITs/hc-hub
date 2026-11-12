<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractAmendmentStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractAmendment;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * Who may see and change a contract. Visibility follows Contract::visibleTo:
 * regional HR within its cities, a manager for their direct reports.
 */
class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('contracts.view');
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->can('contracts.view') && $this->isVisibleTo($user, $contract);
    }

    public function create(User $user): bool
    {
        return $user->can('contracts.create');
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->can('contracts.update') && $this->isVisibleTo($user, $contract);
    }

    public function manageAutoRenew(User $user, Contract $contract): bool
    {
        return HrAccess::is($user, 'HR')
            && $user->can('contracts.update')
            && $this->isVisibleTo($user, $contract);
    }

    public function renew(User $user, Contract $contract): bool
    {
        return $user->can('contracts.create')
            && $contract->is_active
            && $contract->terminated_at === null
            && $this->isVisibleTo($user, $contract);
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $user->can('contracts.delete') && $this->isVisibleTo($user, $contract);
    }

    /**
     * The printed contract carries the pay clause, so it needs pay access.
     */
    public function export(User $user, Contract $contract): bool
    {
        return $user->can('contracts.export')
            && HrAccess::canViewSalary($user)
            && $this->isVisibleTo($user, $contract);
    }

    public function amend(User $user, Contract $contract): bool
    {
        return $this->mayAmend($user, $contract)
            && $contract->canBeAmended()
            && ! $contract->amendments()
                ->whereIn('status', [ContractAmendmentStatus::Draft->value, ContractAmendmentStatus::Approved->value])
                ->exists();
    }

    public function decideAmendment(User $user, ContractAmendment $amendment): bool
    {
        return $amendment->isDraft()
            && $amendment->contract->canBeAmended()
            && $this->mayAmend($user, $amendment->contract);
    }

    public function discardAmendment(User $user, ContractAmendment $amendment): bool
    {
        return ! $amendment->isApplied() && $this->mayAmend($user, $amendment->contract);
    }

    /**
     * Attach the signed sheet, at any point after the amendment was drafted.
     */
    public function attachSignedAmendment(User $user, ContractAmendment $amendment): bool
    {
        return $this->mayAmend($user, $amendment->contract);
    }

    private function mayAmend(User $user, Contract $contract): bool
    {
        return HrAccess::is($user, 'HR')
            && $user->can('contracts.amend')
            && $this->isVisibleTo($user, $contract);
    }

    private function isVisibleTo(User $user, Contract $contract): bool
    {
        return Contract::query()->whereKey($contract->getKey())->visibleTo($user)->exists();
    }
}
