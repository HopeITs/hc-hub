<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProposalLocation;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * A location row is part of its proposal (contracts.md §7, W-34a): whoever
 * may see the proposal sees its rows, whoever may update it changes them.
 * The rows are written only through SaveProposal and the link wrappers,
 * which authorize on the locked proposal.
 */
class ProposalLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('proposals.view');
    }

    public function view(User $user, ProposalLocation $location): bool
    {
        return Gate::forUser($user)->allows('view', $location->proposal);
    }

    public function create(User $user): bool
    {
        return $user->can('proposals.update');
    }

    public function update(User $user, ProposalLocation $location): bool
    {
        return Gate::forUser($user)->allows('update', $location->proposal);
    }

    public function delete(User $user, ProposalLocation $location): bool
    {
        return Gate::forUser($user)->allows('update', $location->proposal);
    }

    public function restore(User $user, ProposalLocation $location): bool
    {
        return false;
    }

    public function forceDelete(User $user, ProposalLocation $location): bool
    {
        return false;
    }
}
