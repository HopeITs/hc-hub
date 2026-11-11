<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Signs out a person whose account was removed or whose linked employee was
 * deactivated, or whose password has changed since this session signed in.
 *
 * Login already refuses such accounts, but an open session or a remember
 * cookie would otherwise outlive the deactivation. The session guard stores a
 * fingerprint of the password hash at login, compared here with the current
 * one. The answer is cached for a minute per user; an answer that would sign
 * someone out is confirmed against the database first, so a login right after
 * a password change is never refused on a stale answer. A failing lookup is
 * reported and the request goes on; a user the last answer had already taken
 * access from is still signed out.
 */
class EnsureAccountIsActive
{
    /**
     * The session key SessionGuard::login() stores the password fingerprint
     * under for the web guard.
     */
    private const PasswordFingerprintSessionKey = 'password_hash_web';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $this->maySessionContinue($request, $user)) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw new AuthenticationException(redirectTo: route('login'));
        }

        return $next($request);
    }

    /**
     * Whether this user's account still backs the session.
     *
     * The password is only held against a session the guard logged this user
     * into (by password, passkey or remember cookie); a user set on the guard
     * directly, as tests do, has no fingerprint to compare. A logged-in session
     * from before fingerprints were checked has none yet either: it is given
     * the current one instead of being signed out.
     *
     * When the lookup fails, the cached answer about
     * access still stands; only the password comparison is skipped.
     */
    private function maySessionContinue(Request $request, User $user): bool
    {
        $session = $request->session();
        $isGuardLogin = (string) $session->get($this->guard()->getName()) === (string) $user->getAuthIdentifier();
        $storedFingerprint = $session->get(self::PasswordFingerprintSessionKey);

        $sessionFingerprint = $isGuardLogin && is_string($storedFingerprint) ? $storedFingerprint : null;
        $needsFingerprint = $isGuardLogin && $sessionFingerprint === null;

        $account = $this->cachedAccount($user);

        if ($account === null) {
            return true;
        }

        if ($needsFingerprint || ! $this->backsSession($account, $sessionFingerprint)) {
            $freshAccount = $this->freshAccount($user);

            if ($freshAccount === null) {
                return ! $account['lost_access'];
            }

            $account = $freshAccount;
        }

        if ($needsFingerprint && ! $account['lost_access']) {
            $session->put(self::PasswordFingerprintSessionKey, $account['password_fingerprint']);

            return true;
        }

        return $this->backsSession($account, $sessionFingerprint);
    }

    /**
     * Whether the account is active and, when the session holds a password
     * fingerprint, still has that password.
     *
     * @param  array{lost_access: bool, password_fingerprint: string}  $account
     */
    private function backsSession(array $account, ?string $sessionFingerprint): bool
    {
        return ! $account['lost_access']
            && ($sessionFingerprint === null || hash_equals($account['password_fingerprint'], $sessionFingerprint));
    }

    /**
     * What was last read about this user's account within the last minute, or null
     * when nothing is cached and the lookup fails.
     *
     * A cache store that fails is reported and never trusted as an answer: the database
     * is asked directly instead, so a deactivation is still noticed while the cache
     * is down.
     *
     * @return array{lost_access: bool, password_fingerprint: string}|null
     */
    private function cachedAccount(User $user): ?array
    {
        try {
            $cached = Cache::get(self::cacheKey($user));
        } catch (Throwable $exception) {
            report($exception);

            return $this->lookUpAccountOrNull($user);
        }

        // An entry in another shape (an older format, a damaged store) is
        // not an answer: ask again.
        if (is_array($cached) && is_bool($cached['lost_access'] ?? null) && is_string($cached['password_fingerprint'] ?? null)) {
            return ['lost_access' => $cached['lost_access'], 'password_fingerprint' => $cached['password_fingerprint']];
        }

        return $this->freshAccount($user);
    }

    /**
     * What the database says about this user's account right now (and cache it), or null
     * when the lookup fails, leaving the cached answer as it was.
     *
     * A failure while caching is reported, but the answer is still returned.
     *
     * @return array{lost_access: bool, password_fingerprint: string}|null
     */
    private function freshAccount(User $user): ?array
    {
        $account = $this->lookUpAccountOrNull($user);

        if ($account === null) {
            return null;
        }

        try {
            Cache::put(self::cacheKey($user), $account, 60);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $account;
    }

    /**
     * What the database says about this user's account right now, or null (reported)
     * when the lookup fails.
     *
     * @return array{lost_access: bool, password_fingerprint: string}|null
     */
    private function lookUpAccountOrNull(User $user): ?array
    {
        try {
            return $this->lookUpAccount($user);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Whether the account still exists and may sign in (as
     * User::hasLostAccess() decides), and the fingerprint of its current
     * password hash, the way the session guard takes it at login.
     *
     * Only this keyed fingerprint is cached, never the hash.
     *
     * @return array{lost_access: bool, password_fingerprint: string}
     */
    private function lookUpAccount(User $user): array
    {
        $account = User::query()->whereKey($user->getKey())->first(['id', 'employee_id', 'password']);

        if ($account === null || $account->hasLostAccess()) {
            return ['lost_access' => true, 'password_fingerprint' => ''];
        }

        return [
            'lost_access' => false,
            'password_fingerprint' => $this->guard()->hashPasswordForCookie((string) $account->password),
        ];
    }

    private function guard(): SessionGuard
    {
        /** @var SessionGuard */
        return Auth::guard('web');
    }

    /**
     * The cache key holding one user's last account check.
     */
    public static function cacheKey(User $user): string
    {
        return 'account-state:'.$user->id;
    }
}
