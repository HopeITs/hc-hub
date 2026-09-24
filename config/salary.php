<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Salary encryption key
    |--------------------------------------------------------------------------
    |
    | Pay figures are encrypted at rest under a key of their own rather than
    | under APP_KEY. The threat this addresses is not an outside attacker but
    | the ordinary movement of a database dump: a copy of the application taken
    | for development, a backup restored on a laptop, a staging box seeded from
    | production. Those copies routinely travel with .env, and APP_KEY with
    | them — so an APP_KEY-encrypted salary is readable everywhere the dump is.
    |
    | Set SALARY_ENCRYPTION_KEY on the production server only, and keep it out
    | of the .env handed to developers. A copy without it simply cannot read
    | the figures: `php artisan salary:key:generate` mints one.
    |
    | Left unset, this falls back to APP_KEY so local development and the test
    | suite keep working against their own data. That fallback is a convenience
    | for disposable databases, not a production configuration — the
    | `salary:key:check` command reports when production is running on it.
    |
    */

    'key' => env('SALARY_ENCRYPTION_KEY'),

    'cipher' => env('SALARY_ENCRYPTION_CIPHER', 'AES-256-CBC'),

    /*
    |--------------------------------------------------------------------------
    | Roles that never see pay figures
    |--------------------------------------------------------------------------
    |
    | super-admin is the developers' role. It carries every permission in the
    | system by design (see config/access-control.php → administrator_roles),
    | which is exactly why it must be named here: without this list, "has every
    | permission" silently includes "reads every salary". A role listed here is
    | refused pay figures regardless of its permissions, and the refusal is
    | evaluated against the *active* role, so switching to HR-ALP to do HR work
    | restores the figures for that city.
    |
    */

    'blind_roles' => ['super-admin'],

];
