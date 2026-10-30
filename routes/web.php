<?php

use App\Http\Middleware\ActiveLivewirePermissionMiddleware;
use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use App\Livewire\AccessManagement\Index as AccessManagementIndex;
use App\Livewire\ActivityLog\ActivityLogModule;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Feedback\FeedbackIndex;
use App\Livewire\Notifications\Index;
use App\Livewire\Settings\Password;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

// Hub (Core) routes. Each module keeps its own routes in modules/{Module}/routes
// and is required from here: public.php outside the locale group, web.php
// inside it behind 'auth'.

require base_path('modules/Hr/routes/public.php');

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    function () {
        Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle)
            ->middleware(['web', ActiveLivewirePermissionMiddleware::class]));

        // EnsureTwoFactorIsEnabled keeps someone whose role requires two-factor
        // on the security page (routes/auth.php) until they have set it up.
        Route::middleware(['auth', EnsureTwoFactorIsEnabled::class])->group(function () {
            Route::get('/', DashboardIndex::class)
                ->name('dashboard');

            Route::redirect('settings', 'settings/password');
            Route::get('settings/password', Password::class)->name('settings.password');

            require base_path('modules/Hr/routes/web.php');

            Route::get('/notifications', Index::class)->name('notifications.index');

            Route::get('/activity-log', ActivityLogModule::class)
                ->name('activity-log.index')
                ->middleware('active_permission:hub.activity-log.view');

            // D3 / DEC-08: in-app feedback is read by the IT team only. Not an
            // active_permission check: that one says yes to every administrator
            // role, HR-SYR included (see User::canReadFeedback()).
            Route::get('/feedback', FeedbackIndex::class)
                ->middleware('can:feedback.read')
                ->name('feedback.index');

            Route::get('/access-management', AccessManagementIndex::class)
                ->middleware('active_permission:hub.access-management.view')
                ->name('access-management.index');

        });

        require __DIR__.'/auth.php';
    },
);
