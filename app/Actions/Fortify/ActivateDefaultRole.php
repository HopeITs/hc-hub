<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Every interactive sign-in starts in the user's first role (lowest id), as
 * HR's old login did: the pivot flag `model_has_roles.is_active` is the only
 * active-role mechanism, and the role switcher changes it afterwards.
 *
 * Called from the login responses (password, and the second step), so a
 * remember-me cookie that signs someone back in keeps the role they were
 * working in.
 */
final class ActivateDefaultRole
{
    public static function for(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $roleIds = $user->roles()->pluck('roles.id')->all();

            if ($roleIds === []) {
                return;
            }

            $user->roles()->updateExistingPivot($roleIds, ['is_active' => false]);

            $roleIdToActivate = min($roleIds);
            $user->roles()->updateExistingPivot($roleIdToActivate, ['is_active' => true]);

            session(['active_role_id' => $roleIdToActivate]);
        });
    }
}
