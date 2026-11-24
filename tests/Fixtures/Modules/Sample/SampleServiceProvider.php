<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Models\User;

/**
 * A module that lives only under tests/ and registers every module contract
 * from its own provider. It proves a new module needs no edit in app/ or in
 * another module (roadmap 5.2).
 */
final class SampleServiceProvider extends ServiceProvider
{
    public const string Ability = 'sample.view';

    public function boot(): void
    {
        Gate::define(self::Ability, fn (User $user): bool => true);

        Route::middleware('web')->prefix('sample')->name('sample.')->group(function (): void {
            Route::get('/', fn (): string => 'sample module')->name('index');
        });

        $modules = $this->app->make(ModuleRegistry::class);
        $modules->addDestinations(SampleDestinations::class);
        $modules->addDeskPanels(SamplePanels::class);
        $modules->addDeskAgenda(SampleAgenda::class);
        $modules->addSubjectLinks(SampleSubjectLinks::class);
        $modules->addPickerScopes(SamplePickerScopes::class);
        $modules->addEmployeeTabs(SampleEmployeeTab::class);
        $modules->addCalendarLayer(SampleCalendarLayer::class);
        $modules->addTimeline(SampleTimeline::class);
        $modules->addSearch(SampleSearch::class);
        $modules->addWorkItems(SampleWorkItems::class);
        $modules->addSchedule(SampleSchedule::class);
        $modules->addMetrics(SampleMetrics::class);
    }
}
