<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\BackoffThrottle;
use App\Support\Turnstile;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;
use SensitiveParameter;

/**
 * Logs people in with their hub account (port of hc-tracking's
 * AuthenticateWithHrAccount; the hub keeps its own password hashes).
 *
 * The login field takes the account's email address or the linked HR
 * employee's username. Only an account whose employee is active may sign in
 * (the same rule EnsureAccountIsActive applies to every later request).
 *
 * On top of Fortify's own limit (a few attempts a minute per identifier and
 * IP), failed logins are limited per IP across all identifiers (password
 * spraying) and per account (per typed identifier when it names none) across
 * all IPs (distributed guessing). Only
 * failures count, so a whole office signing in from one IP is never blocked by
 * its own logins. A browser that signed in to the identifier before is held to
 * a limit of its own instead of the per-identifier one (see RecognisedBrowsers),
 * so that limit cannot be used to lock a colleague out.
 */
class AuthenticateWithPassword
{
    /**
     * Failed logins one IP may make, across all identifiers, per decay window.
     */
    public const MaxFailedLoginsPerIp = 30;

    /**
     * Failed logins one identifier may take, across all IPs, per decay window.
     */
    public const MaxFailedLoginsPerEmail = 10;

    /**
     * How long, in seconds, a failed login counts towards the limits.
     */
    public const FailedLoginDecaySeconds = 900;

    /**
     * Failed password confirmations one user may make from one IP per decay
     * window.
     */
    public const MaxFailedConfirmationsPerUserAndIp = 5;

    /**
     * How long, in seconds, a failed confirmation counts towards the limit
     * per user and IP.
     */
    public const FailedConfirmationsPerUserAndIpDecaySeconds = 60;

    /**
     * Failed password confirmations one user may make, across all IPs, per
     * decay window.
     */
    public const MaxFailedConfirmationsPerUser = 10;

    /**
     * How long, in seconds, a failed confirmation counts towards the limit
     * per user.
     */
    public const FailedConfirmationsPerUserDecaySeconds = 900;

    /**
     * Exponential backoff for the per-identifier and per-user limits: the
     * first time one trips it locks for this many seconds, then double that,
     * and so on.
     */
    public const BackoffBaseSeconds = 900;

    /**
     * The longest a lockout can grow to. Kept to hours, not days, because
     * anyone can fail on purpose against someone else's email to lock it.
     */
    public const BackoffCapSeconds = 21600;

    /**
     * The shortest time, in microseconds, a failed login takes, so a wrong
     * password and an unknown account cannot be told apart by timing.
     */
    public const MinimumLoginMicroseconds = 200000;

    /**
     * The session key holding which user's password a login just checked,
     * and when, until the login completes.
     */
    private const PasswordCheckedSessionKey = 'auth.password_checked';

    private static ?string $dummyHash = null;

    /**
     * Resolve the user for valid credentials.
     *
     * Every failure (unknown identifier, inactive account, wrong password)
     * gets the same answer on the login field and takes the same time (a
     * Timebox, with a dummy hash checked when there is no account), so the
     * form cannot be used to find out which accounts exist.
     *
     * @throws ValidationException
     */
    public function __invoke(Request $request): ?User
    {
        $identifier = trim((string) $request->input(Fortify::username()));
        $password = (string) $request->input('password');

        if ($identifier === '' || $password === '') {
            return null;
        }

        $user = self::findUser($identifier);
        $backoffKey = self::failedLoginsKey($request, $identifier, $user);

        $limits = [
            self::failedLoginsByIpKey($request) => [self::MaxFailedLoginsPerIp, self::FailedLoginDecaySeconds],
            $backoffKey => [self::MaxFailedLoginsPerEmail, self::FailedLoginDecaySeconds],
        ];

        self::ensureIsNotThrottled($request, $limits, Fortify::username(), 'auth.throttle', $backoffKey);

        // A failed human check counts against the IP only, never the
        // identifier, so a script cannot lock someone else's address out with it.
        if (! Turnstile::passes($request)) {
            RateLimiter::hit(self::failedLoginsByIpKey($request), self::FailedLoginDecaySeconds);

            throw ValidationException::withMessages([
                Turnstile::ResponseField => [trans('security.human_check_failed')],
            ]);
        }

        $authenticated = app(Timebox::class)->call(function (Timebox $timebox) use ($user, $password): bool {
            if ($user === null || ! $user->hasActiveEmployee()) {
                Hash::check($password, self::dummyHash());

                return false;
            }

            if (! Hash::check($password, $user->password)) {
                return false;
            }

            $timebox->returnEarly();

            return true;
        }, self::MinimumLoginMicroseconds);

        if (! $authenticated || $user === null) {
            self::recordFailures($limits, $backoffKey, self::MaxFailedLoginsPerEmail);

            app(LoginRateLimiter::class)->increment($request);

            // Fortify's own Failed event would carry the password; fire it
            // without.
            event(new Failed('web', null, [Fortify::username() => $identifier]));

            throw ValidationException::withMessages([
                Fortify::username() => [trans('auth.failed')],
            ]);
        }

        RateLimiter::clear($backoffKey);
        BackoffThrottle::clear($backoffKey);

        $request->session()->put(self::PasswordCheckedSessionKey, [
            'user_id' => $user->id,
            'checked_at' => Date::now()->unix(),
        ]);

        return $user;
    }

    /**
     * Count the password typed at login as a password confirmation, so the
     * person is not asked for it again straight away (e.g. to turn on
     * two-factor authentication). Listens for the guard's Login event.
     *
     * Only the login that checked the password counts: the check is recorded
     * for that user and taken from the session at the next login, so a
     * two-factor challenge completed later still counts from the moment the
     * password was typed, while a passkey or remember-me login (or another
     * user's) after an unfinished password login confirms nothing.
     */
    public static function confirmPasswordCheckedAtLogin(Login $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $session = app('session.store');
        $passwordCheck = $session->pull(self::PasswordCheckedSessionKey);

        if (is_array($passwordCheck) && (string) ($passwordCheck['user_id'] ?? '') === (string) $event->user->getAuthIdentifier()) {
            $session->put('auth.password_confirmed_at', (int) $passwordCheck['checked_at']);
        }
    }

    /**
     * Whether this is the user's password.
     *
     * Every failed check counts towards a limit per user and IP and one per
     * user across all IPs, so this cannot be used to guess the password: once
     * too many checks failed, the next one is refused (even with the right
     * password) until the limit lapses. A failed check is reported as a Failed
     * auth event (without the password), so the auth log sees it.
     *
     * @throws ValidationException
     */
    public static function confirmsPassword(User $user, #[SensitiveParameter] ?string $password, string $field = 'password'): bool
    {
        $request = request();
        $limits = [
            'confirm-password:'.$user->id.'|'.$request->ip() => [self::MaxFailedConfirmationsPerUserAndIp, self::FailedConfirmationsPerUserAndIpDecaySeconds],
            'confirm-password-user:'.$user->id => [self::MaxFailedConfirmationsPerUser, self::FailedConfirmationsPerUserDecaySeconds],
        ];

        $backoffKey = 'confirm-password-user:'.$user->id;

        self::ensureIsNotThrottled($request, $limits, $field, 'security.confirm_throttle', $backoffKey);

        if ((string) $password === '' || ! Hash::check((string) $password, $user->password)) {
            self::recordFailures($limits, $backoffKey, self::MaxFailedConfirmationsPerUser);

            event(new Failed('web', $user, [Fortify::username() => $user->email]));

            return false;
        }

        foreach (array_keys($limits) as $key) {
            RateLimiter::clear($key);
        }

        BackoffThrottle::clear($backoffKey);

        return true;
    }

    /**
     * The account an identifier names: the user with that email, otherwise
     * the user linked to the employee with that username.
     */
    public static function findUser(string $identifier): ?User
    {
        $user = User::query()->where('email', $identifier)->first();

        if ($user !== null || str_contains($identifier, '@')) {
            return $user;
        }

        return User::query()
            ->whereHas('profile', fn ($employee) => $employee->where('username', $identifier))
            ->first();
    }

    /**
     * Refuse the attempt when any of these limits has been reached.
     *
     * @param  array<string, array{int, int}>  $limits  max attempts and decay seconds, by rate limiter key
     * @param  string|null  $backoffKey  a key whose exponential backoff lockout also refuses the attempt
     *
     * @throws ValidationException
     */
    private static function ensureIsNotThrottled(Request $request, array $limits, string $field, string $messageKey, ?string $backoffKey = null): void
    {
        $seconds = $backoffKey === null ? 0 : BackoffThrottle::lockedForSeconds($backoffKey);
        $isThrottled = $seconds > 0;

        foreach ($limits as $key => [$maxAttempts]) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $isThrottled = true;
                $seconds = max($seconds, RateLimiter::availableIn($key));
            }
        }

        if (! $isThrottled) {
            return;
        }

        event(new Lockout($request));

        throw ValidationException::withMessages([
            $field => [trans($messageKey, [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ])],
        ]);
    }

    /**
     * Count one failed attempt against each of these limits.
     *
     * When the limit under $backoffKey is reached by it, that key is locked
     * out for longer than the last time (exponential backoff).
     *
     * @param  array<string, array{int, int}>  $limits  max attempts and decay seconds, by rate limiter key
     */
    private static function recordFailures(array $limits, string $backoffKey, int $backoffMaxAttempts): void
    {
        foreach ($limits as $key => [, $decaySeconds]) {
            RateLimiter::hit($key, $decaySeconds);
        }

        if (RateLimiter::tooManyAttempts($backoffKey, $backoffMaxAttempts)) {
            BackoffThrottle::escalate($backoffKey, self::BackoffBaseSeconds, self::BackoffCapSeconds);
        }
    }

    /**
     * The key failed logins count on across all IPs: the account's own key
     * once the identifier names one (so its email and its username share one
     * limit), the typed identifier otherwise. A browser recognised for the
     * account counts on a key of its own instead (see RecognisedBrowsers).
     */
    private static function failedLoginsKey(Request $request, string $identifier, ?User $user): string
    {
        $email = $user !== null && $user->email !== '' ? $user->email : $identifier;

        return RecognisedBrowsers::failedLoginsKey($request, $email)
            ?? ($user !== null ? 'login-failures-user:'.$user->id : self::failedLoginsByEmailKey($identifier));
    }

    /**
     * A hash to check the typed password against when there is no account,
     * so an unknown identifier costs as much as a wrong password.
     */
    private static function dummyHash(): string
    {
        return self::$dummyHash ??= Hash::make(Str::random(40));
    }

    private static function failedLoginsByIpKey(Request $request): string
    {
        return 'login-failures-ip:'.$request->ip();
    }

    /**
     * The per-identifier key holds a hash of it, so any value fits in the
     * cache key and no address is stored in the cache.
     */
    private static function failedLoginsByEmailKey(string $email): string
    {
        return 'login-failures-email:'.RecognisedBrowsers::emailFingerprint($email);
    }
}
