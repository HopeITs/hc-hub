<?php

declare(strict_types=1);

namespace Modules\Hr;

use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Hr\Console\ApplyContractAmendmentsCommand;
use Modules\Hr\Console\CheckContractAmendmentsCommand;
use Modules\Hr\Console\DeactivateExpiredContractsCommand;
use Modules\Hr\Console\ImportHrCommand;
use Modules\Hr\Console\Journeys\DeactivateDepartedEmployees;
use Modules\Hr\Console\Journeys\SendContractExpirationWarnings;
use Modules\Hr\Console\Journeys\SendOrientationReminders;
use Modules\Hr\Console\Journeys\TrackRenewalJourneys;
use Modules\Hr\Console\ProcessContractAutoRenewalsCommand;
use Modules\Hr\Console\RecountPositionSeatsCommand;
use Modules\Hr\Console\SendLeaveDecisionRemindersCommand;
use Modules\Hr\Support\Journeys\JourneyHooks;
use Modules\Hr\Support\Recruitment\Console\AnalyzeRecruitmentCvs;
use Modules\Hr\Support\Recruitment\Console\ApplyDueRecruitmentRequests;
use Modules\Hr\Support\Recruitment\Console\CheckRecruitmentScanner;
use Modules\Hr\Support\Recruitment\Console\ExpireDueHiringCampaigns;
use Modules\Hr\Support\Recruitment\Console\ExpireRecruitmentJobOffers;
use Modules\Hr\Support\Recruitment\Console\RedispatchRecruitmentRejectionNotices;
use Modules\Hr\Support\Recruitment\Console\ScanRecruitmentFiles;
use Modules\Hr\Support\Recruitment\Console\SeedRecruitmentDemoApplicationLink;
use Modules\Hr\Support\Recruitment\Console\SendRecruitmentInterviewReminders;
use Modules\Hr\Support\Recruitment\Console\SnapshotRecruitmentIndicators;
use Modules\Hr\Support\Recruitment\Console\SweepRecruitmentRetention;
use Modules\Hr\Support\Recruitment\Console\SyncRecruitmentCampaignStates;
use Modules\Hr\Support\Recruitment\RecruitmentBootstrap;
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
            ApplyContractAmendmentsCommand::class,
            CheckContractAmendmentsCommand::class,
            SendOrientationReminders::class,
            SendContractExpirationWarnings::class,
            TrackRenewalJourneys::class,
            DeactivateDepartedEmployees::class,
            DeactivateExpiredContractsCommand::class,
            ProcessContractAutoRenewalsCommand::class,
            SendLeaveDecisionRemindersCommand::class,
            RecountPositionSeatsCommand::class,
            AnalyzeRecruitmentCvs::class,
            ApplyDueRecruitmentRequests::class,
            CheckRecruitmentScanner::class,
            ExpireDueHiringCampaigns::class,
            ExpireRecruitmentJobOffers::class,
            RedispatchRecruitmentRejectionNotices::class,
            ScanRecruitmentFiles::class,
            SeedRecruitmentDemoApplicationLink::class,
            SendRecruitmentInterviewReminders::class,
            SnapshotRecruitmentIndicators::class,
            SweepRecruitmentRetention::class,
            SyncRecruitmentCampaignStates::class,
        ]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            // Evening contract runs, in this order: amendments take effect on their date, then ended
            // contracts go inactive and free their seat, then the ones that renew get a successor
            // (which so inherits the amended terms). The weekly check logs a broken amendment history.
            $schedule->command(ApplyContractAmendmentsCommand::class)->dailyAt('21:25')->withoutOverlapping()->onOneServer();
            $schedule->command(DeactivateExpiredContractsCommand::class)->dailyAt('21:30')->withoutOverlapping()->onOneServer();
            $schedule->command(ProcessContractAutoRenewalsCommand::class)->dailyAt('21:35')->withoutOverlapping()->onOneServer();
            $schedule->command(CheckContractAmendmentsCommand::class)->weeklyOn(0, '06:00')->onOneServer();

            // Journeys (as the HR app ran them): expiry warnings and overdue orientations in the
            // morning; renewal journeys, then departed volunteers, after the evening contract runs.
            $schedule->command(SendContractExpirationWarnings::class)->dailyAt('08:00')->withoutOverlapping()->onOneServer();
            $schedule->command(SendOrientationReminders::class)->dailyAt('08:10')->withoutOverlapping()->onOneServer();
            $schedule->command(TrackRenewalJourneys::class)->dailyAt('21:45')->withoutOverlapping()->onOneServer();
            $schedule->command(DeactivateDepartedEmployees::class)->dailyAt('21:50')->withoutOverlapping()->onOneServer();

            // One reminder per leave request still waiting for its decision.
            $schedule->command(SendLeaveDecisionRemindersCommand::class)->dailyAt('08:05')->withoutOverlapping()->onOneServer();

            // Recruitment (as the HR app ran it). Due position changes early, before the evening
            // contract runs read the seats; campaign windows every minute; the antivirus safety
            // net, offers past their answer date, interview reminders and rejection emails a down
            // queue held; retention dates and erasure requests at night.
            $schedule->command(ApplyDueRecruitmentRequests::class)->dailyAt('00:30')->onOneServer();
            $schedule->command(SyncRecruitmentCampaignStates::class)->everyMinute()->withoutOverlapping()->onOneServer();
            $schedule->command(ScanRecruitmentFiles::class)->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
            $schedule->command(SendRecruitmentInterviewReminders::class)->everyFiveMinutes()->withoutOverlapping()->onOneServer();
            $schedule->command(ExpireRecruitmentJobOffers::class)->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
            $schedule->command(RedispatchRecruitmentRejectionNotices::class)->everyFiveMinutes()->withoutOverlapping()->onOneServer();
            $schedule->command(SweepRecruitmentRetention::class)->dailyAt('02:15')->withoutOverlapping()->onOneServer();
        });

        // Rate limiters, observers and bindings; outside the routes so it also runs under route:cache.
        RecruitmentBootstrap::boot();

        // Contract writes that start or move an employee journey.
        JourneyHooks::register();

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
