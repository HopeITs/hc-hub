<?php

use App\Http\Controllers\EmailTwoFactorController;
use App\Http\Controllers\EmployeeAvatarController;
use App\Http\Controllers\EmployeePickerOptionsController;
use App\Http\Controllers\MarkNotificationReadController;
use App\Http\Controllers\MarkTourSeenController;
use App\Http\Controllers\OpenNotificationController;
use App\Http\Controllers\SwitchLocaleController;
use App\Http\Controllers\ToggleDeskItemController;
use App\Http\Controllers\ToggleNavPinController;
use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\User;

/*
| Hub-wide routes: sign-in, locale, the Desk, notifications and user admin.
| Each module registers its own pages from its service provider
| (modules/Tracking/routes/web.php).
*/

Route::get('/', fn () => redirect()->route(Auth::check() ? 'dashboard' : 'login'))->name('home');

Route::post('locale', SwitchLocaleController::class)->middleware('throttle:locale')->name('locale.update');

// Second login step for people who turned on email codes (after the HR password).
Route::middleware('guest')->controller(EmailTwoFactorController::class)->group(function () {
    Route::get('email-two-factor-challenge', 'create')->name('email-two-factor.login');
    Route::post('email-two-factor-challenge', 'store')->middleware('throttle:two-factor')->name('email-two-factor.login.store');
    Route::post('email-two-factor-challenge/resend', 'resend')->middleware('throttle:email-two-factor-resend')->name('email-two-factor.resend');
});

Route::middleware(['auth', EnsureTwoFactorIsEnabled::class])->group(function () {
    Route::get('employees/picker-options', EmployeePickerOptionsController::class)->name('employees.picker-options');
    Route::get('employees/{employee}/avatar', EmployeeAvatarController::class)->whereNumber('employee')->name('employees.avatar');
    Route::post('tour/seen', MarkTourSeenController::class)->middleware('throttle:locale')->name('tour.seen');
    Route::post('nav/pins', ToggleNavPinController::class)->middleware('throttle:locale')->name('nav.pins.toggle');
    Route::post('desk/items', ToggleDeskItemController::class)->middleware('throttle:desk')->name('desk.items.toggle');

    Route::livewire('home', 'pages::desk.index')->name('dashboard');

    Route::livewire('admin/notification-settings', 'pages::admin.notification-settings')
        ->can('notification-settings.manage')
        ->name('admin.notification-settings.edit');

    Route::livewire('activity', 'pages::activity.index')
        ->can('activity-log.view')
        ->name('activity.index');

    Route::livewire('notifications-settings', 'pages::notifications.settings')
        ->name('notification-settings.edit');

    Route::livewire('notifications', 'pages::notifications.index')
        ->name('notifications.index');

    Route::get('notifications/{notification}/open', OpenNotificationController::class)
        ->name('notifications.open');

    Route::post('notifications/{notification}/read', MarkNotificationReadController::class)
        ->name('notifications.read');

    Route::livewire('admin/users', 'pages::admin.users')
        ->can('viewAny', User::class)
        ->name('admin.users.index');
});

require __DIR__.'/settings.php';
