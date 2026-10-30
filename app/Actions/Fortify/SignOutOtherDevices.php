<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ends every session of the account except the one making this request:
 * the other database session rows go, and the remember token is rotated so
 * no other browser's remember-me cookie works any more. This browser keeps
 * its own remember-me cookie, reissued for the new token.
 *
 * Used by Settings > Security ("sign out other devices") and after a
 * password change.
 */
final class SignOutOtherDevices
{
    public static function run(Request $request, User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->where('id', '!=', session()->getId())
                ->delete();
        }

        self::rotateRememberToken($request, $user);

        event(new OtherDeviceLogout('web', $user));
    }

    /**
     * Give the account a new remember token. When this request came with a
     * remember-me cookie, this device gets a fresh one for the new token,
     * built as SessionGuard::queueRecallerCookie() builds it but without
     * logging in again, which would fire a Login event.
     */
    private static function rotateRememberToken(Request $request, User $user): void
    {
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');

        $guard->getProvider()->updateRememberToken($user, $token = Str::random(60));

        if (! $request->cookies->has($guard->getRecallerName())) {
            return;
        }

        $cookies = $guard->getCookieJar();

        $cookies->queue($cookies->make(
            $guard->getRecallerName(),
            $user->getAuthIdentifier().'|'.$token.'|'.$guard->hashPasswordForCookie($user->getAuthPassword()),
            (int) config('auth.guards.web.remember', 576000),
        ));
    }
}
