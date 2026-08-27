<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Facades\Activity;

/**
 * Terminates every existing avenue of access for a user.
 *
 * Marking an employee inactive previously only blocked the *next* login. A
 * session that was already established kept working until it expired — up to
 * SESSION_LIFETIME minutes of full access for someone whose employment had
 * just ended. For an organization where deactivation may be a protection
 * measure rather than routine offboarding, that window is the whole problem.
 *
 * Revocation is deliberately blunt and idempotent: it can be re-run safely,
 * and it is called from the Employee observer so no deactivation path can
 * forget it.
 */
class AccessRevoker
{
    public function revoke(User $user, string $reason): void
    {
        DB::transaction(function () use ($user, $reason): void {
            $sessions = $this->deleteSessions($user);
            $this->clearRememberToken($user);
            $this->deactivateRoles($user);
            $this->revokeApiTokens($user);
            $this->record($user, $reason, $sessions);
        });
    }

    /**
     * Drop every server-side session row for this user.
     *
     * With the database session driver this takes effect on the very next
     * request: the session id in the user's cookie no longer resolves, so the
     * request is treated as unauthenticated.
     */
    private function deleteSessions(User $user): int
    {
        if (config('session.driver') !== 'database' || ! Schema::hasTable(config('session.table', 'sessions'))) {
            // Other drivers cannot be enumerated by user id. EnsureAccountIsActive
            // is the backstop that rejects those sessions on their next request.
            return 0;
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->delete();
    }

    /**
     * Invalidate the "remember me" cookie.
     *
     * Without this, deleting the session simply causes the recaller cookie to
     * mint a fresh one.
     */
    private function clearRememberToken(User $user): void
    {
        $user->forceFill(['remember_token' => null])->saveQuietly();
    }

    /**
     * Stand every role assignment down.
     *
     * hasActivePermission() resolves against the active role, so leaving none
     * active means no permission check can succeed even if a session survives.
     */
    private function deactivateRoles(User $user): void
    {
        $roleIds = $user->roles()->pluck('roles.id')->all();

        if ($roleIds !== []) {
            $user->roles()->updateExistingPivot($roleIds, ['is_active' => false]);
        }
    }

    /** Personal access tokens, if Sanctum is ever added to the project. */
    private function revokeApiTokens(User $user): void
    {
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
    }

    private function record(User $user, string $reason, int $sessions): void
    {
        Activity::useLog('security')
            ->performedOn($user)
            ->withProperties([
                'reason' => $reason,
                'sessions_deleted' => $sessions,
            ])
            ->log('access_revoked');
    }
}
