<?php

declare(strict_types=1);

namespace Modules\Tracking;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Console\Commands\BackfillMonthlyReports;
use Modules\Tracking\Console\Commands\BackfillProposalLocations;
use Modules\Tracking\Console\Commands\EnsureAnnualPlans2026;
use Modules\Tracking\Console\Commands\ImportProgramFramework;
use Modules\Tracking\Console\Commands\ImportStrategyPillars;
use Modules\Tracking\Console\Commands\MapProjectCenters;
use Modules\Tracking\Console\Commands\PrefillActiveReportTemplates;
use Modules\Tracking\Console\Commands\PrepareMonthlyReports;
use Modules\Tracking\Console\Commands\RemindAnnualPlanUpdates;
use Modules\Tracking\Console\Commands\RemindProposalOwners;
use Modules\Tracking\Console\Commands\SeedIndicatorLibrary;
use Modules\Tracking\Console\Commands\SendMonthlyDigest;
use Modules\Tracking\Enums\DonorAttribution;
use Modules\Tracking\Events\FundingAgreementRecorded;
use Modules\Tracking\Events\ProjectActivated;
use Modules\Tracking\Events\ProjectPeriodChanged;
use Modules\Tracking\Events\ProposalStatusChanged;
use Modules\Tracking\Listeners\GenerateExpectedTransfersOnAgreementRecorded;
use Modules\Tracking\Listeners\GenerateMonthlyReportsForProject;
use Modules\Tracking\Listeners\PrefillReportTemplates;
use Modules\Tracking\Listeners\RebaseProposalPlanOnApproval;
use Modules\Tracking\Listeners\ShiftActivitySchedulesOnApproval;
use Modules\Tracking\Models\AnnualPlan;
use Modules\Tracking\Models\IndicatorProgress;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Report;
use Modules\Tracking\Models\Result;
use Modules\Tracking\Models\StrategicPillar;
use Modules\Tracking\Models\Strategy;
use Modules\Tracking\Models\Task;
use Modules\Tracking\Models\TaskAssignee;
use Modules\Tracking\Observers\ProgramObserver;
use Modules\Tracking\Policies\ProgramPolicy;
use Modules\Tracking\Support\SupervisionMap;

/**
 * The tracking module: strategies, programs, projects, proposals, funding,
 * M&E and tasks. Its pages are Livewire components in the `tracking::`
 * namespace, its other views are under the `tracking::` view namespace, and
 * its routes keep the URLs and names they had before the module existed.
 *
 * Policies for Modules\Tracking\Models\* are found by Laravel's own guess
 * (Modules\Tracking\Policies\{Model}Policy). Program lives in Core, so its
 * policy is registered here by hand.
 */
class TrackingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One instance per request or job, so queue workers never hold stale values.
        $this->app->scoped(SupervisionMap::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'tracking');

        // Page SFCs: Route::livewire('...', 'tracking::proposals.index').
        Livewire::addNamespace('tracking', viewPath: __DIR__.'/resources/views/pages');

        // Component SFCs keep their short names (<livewire:proposals.logframe />).
        Livewire::addLocation(viewPath: __DIR__.'/resources/views/components');

        Gate::policy(Program::class, ProgramPolicy::class);

        $this->registerListeners();
        $this->registerEmployeeRelations();
        $this->registerCoreRelations();

        $this->commands([
            BackfillMonthlyReports::class,
            BackfillProposalLocations::class,
            EnsureAnnualPlans2026::class,
            ImportProgramFramework::class,
            ImportStrategyPillars::class,
            MapProjectCenters::class,
            PrefillActiveReportTemplates::class,
            PrepareMonthlyReports::class,
            RemindAnnualPlanUpdates::class,
            RemindProposalOwners::class,
            SeedIndicatorLibrary::class,
            SendMonthlyDigest::class,
        ]);

        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(__DIR__.'/routes/web.php');
        }
    }

    /**
     * The listeners event discovery used to find in app/Listeners, registered
     * the same way discovery registers them (Class@method).
     */
    protected function registerListeners(): void
    {
        Event::listen(FundingAgreementRecorded::class, GenerateExpectedTransfersOnAgreementRecorded::class.'@handle');
        Event::listen(ProjectActivated::class, GenerateMonthlyReportsForProject::class.'@handleProjectActivated');
        Event::listen(ProjectPeriodChanged::class, GenerateMonthlyReportsForProject::class.'@handleProjectPeriodChanged');
        Event::listen(ProposalStatusChanged::class, PrefillReportTemplates::class.'@handle');
        Event::listen(ProposalStatusChanged::class, RebaseProposalPlanOnApproval::class.'@handle');
        Event::listen(ProposalStatusChanged::class, ShiftActivitySchedulesOnApproval::class.'@handle');
    }

    /**
     * Tracking's relations on HR's Employee, kept here so HR does not depend
     * on Tracking. Names and keys are the ones Employee used to declare, so
     * eager loads, whereHas() and Blade keep working.
     */
    protected function registerEmployeeRelations(): void
    {
        Employee::resolveRelationUsing('managedAnnualPlans', fn (Employee $employee): HasMany => $employee->hasMany(AnnualPlan::class, 'manager_id'));
        Employee::resolveRelationUsing('projects', fn (Employee $employee): HasMany => $employee->hasMany(Project::class, 'user_id'));
        Employee::resolveRelationUsing('responsibleMilestones', fn (Employee $employee): HasMany => $employee->hasMany(Milestone::class, 'responsible_user_id'));
        Employee::resolveRelationUsing('assignedTasks', fn (Employee $employee): HasManyThrough => $employee->hasManyThrough(Task::class, TaskAssignee::class, 'employee_id', 'id', 'id', 'task_id'));
    }

    /**
     * Tracking's side of the Core models, kept here so Core does not depend
     * on Tracking (D10): the relations Program and User used to declare (same
     * names and keys), the donor attribution cast and ProgramObserver.
     */
    protected function registerCoreRelations(): void
    {
        Program::castAttributeUsing('donor_attribution', DonorAttribution::class);
        Program::observe(ProgramObserver::class);

        Program::resolveRelationUsing('strategy', fn (Program $program): BelongsTo => $program->belongsTo(Strategy::class, 'strategy_id', 'id', 'strategy'));
        Program::resolveRelationUsing('pillar', fn (Program $program): BelongsTo => $program->belongsTo(StrategicPillar::class, 'strategic_pillar_id', 'id', 'pillar'));
        Program::resolveRelationUsing('projects', fn (Program $program): HasMany => $program->hasMany(Project::class));
        Program::resolveRelationUsing('annualPlans', fn (Program $program): HasMany => $program->hasMany(AnnualPlan::class));
        Program::resolveRelationUsing('results', fn (Program $program): HasMany => $program->hasMany(Result::class)->programFramework());

        User::resolveRelationUsing('createdTasks', fn (User $user): HasMany => $user->hasMany(Task::class, 'created_by'));
        User::resolveRelationUsing('reports', fn (User $user): HasMany => $user->hasMany(Report::class));
        User::resolveRelationUsing('indicatorProgress', fn (User $user): HasMany => $user->hasMany(IndicatorProgress::class, 'recorded_by'));
    }
}
