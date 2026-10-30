<?php

namespace App\Providers;

use App\Actions\Fortify\AuthenticateWithPassword;
use App\Actions\Fortify\DisableTwoFactorAuthenticationUnlessRequired;
use App\Actions\Fortify\RedirectIfTwoFactorRequired;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Http\Responses\PasswordResetLinkRequestedResponse;
use App\Http\Responses\TwoFactorLoginResponse;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkeys;
use Livewire\Component;
use Livewire\Livewire;

/**
 * The hub's sign-in, ported from hc-tracking's Fortify set-up (task 1.1):
 * password (email or HR username), then the authenticator app or an emailed
 * code, trusted browsers, passkeys for password confirmation, Cloudflare
 * Turnstile, and the same rate limits.
 *
 * Fortify's routes are registered from routes/auth.php, inside the locale
 * group, instead of by Fortify itself.
 */
class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Fortify::ignoreRoutes();

        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, TwoFactorLoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
        $this->app->bind(SuccessfulPasswordResetLinkRequestResponse::class, PasswordResetLinkRequestedResponse::class);
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkRequestedResponse::class);

        // Fortify's own "disable two-factor" endpoint cannot switch it off for
        // a role that requires it; half-finished set-ups are still cleared.
        $this->app->bind(DisableTwoFactorAuthentication::class, DisableTwoFactorAuthenticationUnlessRequired::class);
    }

    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureLivewire();
    }

    /**
     * Livewire re-runs only its own list of route middleware on update
     * requests. The two-factor requirement joins it, so a component rendered
     * on a page that requires two-factor cannot be driven by someone who
     * still has to turn it on; components rendered on the security page
     * itself (the header's notification bell, say) carry that page's route
     * instead, so every hydrated component outside the enrolment set is
     * checked as well.
     */
    private function configureLivewire(): void
    {
        Livewire::addPersistentMiddleware([EnsureTwoFactorIsEnabled::class]);

        Livewire::listen('hydrate', function (mixed $component): void {
            if ($component instanceof Component) {
                EnsureTwoFactorIsEnabled::guardLivewireComponent($component);
            }
        });
    }

    private function configureActions(): void
    {
        // After the password: the authenticator app challenge, or an emailed code.
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorRequired::class);

        Fortify::authenticateUsing(new AuthenticateWithPassword);
        Fortify::confirmPasswordsUsing(
            fn (User $user, ?string $password): bool => AuthenticateWithPassword::confirmsPassword($user, $password),
        );
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // The password typed at login also counts as a password confirmation.
        Event::listen(fn (Login $event) => AuthenticateWithPassword::confirmPasswordCheckedAtLogin($event));

        // A passkey skips the password check, so the account must still be active.
        Passkeys::authorizeLoginUsing(fn (Request $request, PasskeyUser $user): bool => $user instanceof User && $user->hasActiveEmployee());
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
    }

    /**
     * The same limits as hc-tracking.
     *
     * The `two-factor` limiter guards both second steps: Fortify's POST
     * /two-factor-challenge (authenticator or recovery code) and the hub's
     * POST email-two-factor-challenge. Whoever reaches either already knows
     * the account's password, so a per-minute limit alone still allowed
     * about 7,200 guesses a day; the hourly and daily caps bring that down
     * to 30. Each limit needs its own `by` key: ThrottleRequests keys a
     * limit by limiter name plus `by` value, not by its decay, so limits
     * sharing a key would share one counter.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            $loginId = $request->session()->get('login.id');

            return [
                Limit::perMinute(5)->by('minute:'.$loginId),
                Limit::perHour(15)->by('hour:'.$loginId),
                Limit::perDay(30)->by('day:'.$loginId),
            ];
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            // The first key is the caller's to choose (any credential id, or
            // a fresh session on every request), so it alone limits nothing
            // for a script. The second caps each IP whatever it sends.
            return [
                Limit::perMinute(10)->by(($credentialId ?: $request->session()->getId()).'|'.$request->ip()),
                Limit::perMinute(60)->by('ip:'.$request->ip()),
            ];
        });

        // "Send me a reset link", per address and IP, as HR's old form had it.
        RateLimiter::for('password-reset-link', fn (Request $request) => Limit::perMinute(5)->by(
            Str::lower((string) $request->input('email')).'|'.$request->ip(),
        ));

        // Resending the emailed code: per pending login and per IP, so neither
        // many IPs nor many guest sessions can spam one inbox.
        RateLimiter::for('email-two-factor-resend', fn (Request $request) => [
            Limit::perMinute(2)->by('minute:'.$request->session()->get('login.id')),
            Limit::perHour(10)->by('hour:'.$request->session()->get('login.id')),
            Limit::perMinute(5)->by($request->ip()),
        ]);
    }
}
