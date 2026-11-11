<?php

declare(strict_types=1);

namespace Modules\Tracking\Policies;

use Modules\Core\Models\User;
use Modules\Tracking\Models\ProgramRequest;

/**
 * Program requests (contracts.md §7, W-34a) are opened by SaveProposal for
 * proposal editors and handled on the admin page by `programs.update`
 * holders, who link the HR program or decline the request. Nobody deletes
 * one by hand: it goes with its location row.
 */
class ProgramRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('programs.update');
    }

    public function view(User $user, ProgramRequest $request): bool
    {
        return $user->can('programs.update') || $user->can('proposals.view');
    }

    public function create(User $user): bool
    {
        return $user->can('proposals.update');
    }

    public function update(User $user, ProgramRequest $request): bool
    {
        return $user->can('programs.update');
    }

    public function delete(User $user, ProgramRequest $request): bool
    {
        return false;
    }

    public function restore(User $user, ProgramRequest $request): bool
    {
        return false;
    }

    public function forceDelete(User $user, ProgramRequest $request): bool
    {
        return false;
    }
}
