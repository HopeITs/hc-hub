<?php

use App\Http\Controllers\Auth\EmailTwoFactorController;
use App\Livewire\Settings\Security;
use Illuminate\Support\Facades\Route;

/*
| Sign-in for the whole hub (task 1.1): Fortify's routes (login, logout,
| two-factor challenge, password confirmation and reset, passkeys, the
| two-factor settings endpoints), required here so they sit inside the
| /en, /ar locale group like every other page. FortifyServiceProvider stops
| Fortify from registering them a second time without the prefix.
*/
Route::group(['namespace' => 'Laravel\Fortify\Http\Controllers'], function () {
    require base_path('vendor/laravel/fortify/routes/routes.php');
});

// Fortify gives "send me a reset link" no limit of its own.
$routes = app('router')->getRoutes();
$routes->refreshNameLookups();
$routes->getByName('password.email')?->middleware('throttle:password-reset-link');

// Second sign-in step for people who use emailed codes (after the password).
Route::middleware('guest')->controller(EmailTwoFactorController::class)->group(function () {
    Route::get('email-two-factor-challenge', 'create')->name('email-two-factor.login');
    Route::post('email-two-factor-challenge', 'store')->middleware('throttle:two-factor')->name('email-two-factor.login.store');
    Route::post('email-two-factor-challenge/resend', 'resend')->middleware('throttle:email-two-factor-resend')->name('email-two-factor.resend');
});

/*
| The security page stays open to someone who still has to turn on two-factor
| authentication: EnsureTwoFactorIsEnabled sends them here to do it.
*/
Route::middleware(['auth', 'password.confirm'])->group(function () {
    Route::get('settings/security', Security::class)->name('settings.security');
});
