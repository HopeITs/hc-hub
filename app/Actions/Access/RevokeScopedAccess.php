<?php

declare(strict_types=1);

namespace App\Actions\Access;

use App\Models\AccessGrant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Take a scoped grant back (roadmap 4.4): revoked_at and revoked_by are
 * filled and the row stays, so who could do what, and until when, is kept.
 */
class RevokeScopedAccess
{
    /**
     * @return bool false when the grant had already been revoked
     */
    public function handle(AccessGrant $grant): bool
    {
        Gate::authorize('access-management.manage-permissions');

        if ($grant->revoked_at !== null) {
            return false;
        }

        $grant->forceFill(['revoked_at' => now(), 'revoked_by' => Auth::id()])->save();

        return true;
    }
}
