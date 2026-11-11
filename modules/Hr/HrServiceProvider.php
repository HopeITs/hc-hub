<?php

declare(strict_types=1);

namespace Modules\Hr;

use Illuminate\Support\ServiceProvider;
use Modules\Hr\Console\ImportHrCommand;
use Modules\Hr\Support\SalaryCipher;

/**
 * The HR module: employees, positions, contracts, leave, journeys, archives,
 * news and recruitment. So far only its data layer: the tables (migrations
 * here; users, programs, cities and centers are Core) and the models.
 * `hub:import-hr` copies the HR app's database in until launch.
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

        $this->commands([
            ImportHrCommand::class,
        ]);
    }
}
