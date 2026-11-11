<?php

declare(strict_types=1);

namespace Modules\Hr;

use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Hr\Console\ImportHrCommand;
use Modules\Hr\Support\SalaryCipher;

/**
 * The HR module: employees, positions, contracts, leave, journeys, archives,
 * news and recruitment. Users, programs, cities and centers are Core; HR only
 * reads programs and centers. `hub:import-hr` copies the HR app's database in
 * until launch.
 *
 * Pages are Livewire SFCs in the `hr::` namespace (Route::livewire('...',
 * 'hr::employees.index')), other views use the `hr::` view namespace. Every
 * file in routes/ is one area and is loaded inside the auth + 2FA group, so
 * areas never share a routes file. Policies for Modules\Hr\Models\* are found
 * by Laravel's own guess (Modules\Hr\Policies\{Model}Policy).
 */
class HrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SalaryCipher::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'hr');
        Livewire::addNamespace('hr', viewPath: __DIR__.'/resources/views/pages');

        $this->commands([
            ImportHrCommand::class,
        ]);

        if (! $this->app->routesAreCached()) {
            $this->loadAreaRoutes();
        }
    }

    /**
     * Each routes/*.php file holds one HR area's pages.
     */
    protected function loadAreaRoutes(): void
    {
        $files = glob(__DIR__.'/routes/*.php') ?: [];

        Route::middleware(['web', 'auth', EnsureTwoFactorIsEnabled::class])->group(function () use ($files): void {
            foreach ($files as $file) {
                require $file;
            }
        });

        // Pages a guest may open (the careers site): routes/public/*.php.
        $publicFiles = glob(__DIR__.'/routes/public/*.php') ?: [];

        Route::middleware('web')->group(function () use ($publicFiles): void {
            foreach ($publicFiles as $file) {
                require $file;
            }
        });
    }
}
