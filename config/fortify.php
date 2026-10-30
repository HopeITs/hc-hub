<?php

use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| Fortify (sign-in for the whole hub)
|--------------------------------------------------------------------------
|
| Ported from hc-tracking (task 1.1). The routes are not registered by
| Fortify itself: FortifyServiceProvider calls Fortify::ignoreRoutes() and
| routes/auth.php requires Fortify's route file inside the /en, /ar locale
| group, so every sign-in page is localized like the rest of the hub.
|
*/

return [

    'guard' => 'web',

    'passwords' => 'users',

    /*
    | The login form's identifier field. It accepts an email address or an
    | HR employee username (see App\Actions\Fortify\AuthenticateWithPassword).
    */
    'username' => 'email',

    'email' => 'email',

    'lowercase_usernames' => true,

    /*
    | Where Fortify sends people after signing in. LoginResponse and
    | TwoFactorLoginResponse redirect to the localized dashboard route
    | instead; this is only the fallback the passkeys package reads.
    */
    'home' => '/',

    'prefix' => '',

    'domain' => null,

    /*
    | The routes sit inside routes/web.php, which already runs the `web`
    | group, so nothing is added here.
    */
    'middleware' => ['web'],

    /*
    | The limiters are defined in FortifyServiceProvider::configureRateLimiting().
    */
    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
        'passkeys' => 'passkeys',
    ],

    'views' => true,

    'passkeys' => [
        'relying_party_id' => parse_url((string) config('app.url'), PHP_URL_HOST),
        // A WebAuthn origin is scheme://host[:port], so any path or trailing
        // slash on APP_URL is cut off here.
        'allowed_origins' => [preg_replace('#^([a-z][a-z0-9+.-]*://[^/?\#]+).*$#i', '$1', (string) config('app.url'))],
        // `?:` so an empty PASSKEYS_USER_HANDLE_SECRET= also falls back to
        // APP_KEY rather than keying user handles with an empty string.
        'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET') ?: config('app.key'),
        'timeout' => 60000,
    ],

    'features' => [
        // Registration is disabled: accounts are created by HR with an
        // employee. Email verification is off: the User model never required it.
        //
        // Password reset stays on (unlike hc-tracking): hub passwords are the
        // hub's own, so a reset link can change them.
        Features::resetPasswords(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
        Features::passkeys([
            'confirmPassword' => true,
        ]),
    ],

];
