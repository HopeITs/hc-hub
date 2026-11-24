<?php

declare(strict_types=1);

namespace Modules\Hr;

use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use App\Support\EmployeeLookup;
use App\Support\Modules\ModuleRegistry;
use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Console\ApplyContractAmendmentsCommand;
use Modules\Hr\Console\BackfillEntitlementsCommand;
use Modules\Hr\Console\CheckContractAmendmentsCommand;
use Modules\Hr\Console\CopyHrFilesCommand;
use Modules\Hr\Console\DeactivateExpiredContractsCommand;
use Modules\Hr\Console\HrSchedule;
use Modules\Hr\Console\ImportHrCommand;
use Modules\Hr\Console\Journeys\DeactivateDepartedEmployees;
use Modules\Hr\Console\Journeys\SendContractExpirationWarnings;
use Modules\Hr\Console\Journeys\SendOnboardingStepReminders;
use Modules\Hr\Console\Journeys\SendOrientationReminders;
use Modules\Hr\Console\Journeys\TrackRenewalJourneys;
use Modules\Hr\Console\LoadMailListsCommand;
use Modules\Hr\Console\MoveHrMediaCommand;
use Modules\Hr\Console\NotifyBirthdayConflictsCommand;
use Modules\Hr\Console\NotifyCriticalPositionsCommand;
use Modules\Hr\Console\ProcessContractAutoRenewalsCommand;
use Modules\Hr\Console\PurgeUploadedPunchesCommand;
use Modules\Hr\Console\QueueMailListStartersCommand;
use Modules\Hr\Console\RecountPositionSeatsCommand;
use Modules\Hr\Console\RemindMissingLeavingReasonsCommand;
use Modules\Hr\Console\ReportAccountGapsCommand;
use Modules\Hr\Console\ReportRecruitmentAccessCommand;
use Modules\Hr\Console\SendAttendanceRemindersCommand;
use Modules\Hr\Console\SendLeaveDecisionRemindersCommand;
use Modules\Hr\Console\StartProbationReviewsCommand;
use Modules\Hr\Livewire\Chat\Dock as ChatDock;
use Modules\Hr\Livewire\Chat\HeaderButton as ChatHeaderButton;
use Modules\Hr\Livewire\Chat\Page as ChatPage;
use Modules\Hr\Livewire\Chat\Window as ChatWindow;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Calendar\HolidayCalendarLayer;
use Modules\Hr\Support\Calendar\LeaveCalendarLayer;
use Modules\Hr\Support\CriticalPositions\CandidateAiRanker;
use Modules\Hr\Support\CriticalPositions\NullCandidateAiRanker;
use Modules\Hr\Support\Desk\HrDestinations;
use Modules\Hr\Support\Desk\HrPanels;
use Modules\Hr\Support\Employees\HrEmployeeLookup;
use Modules\Hr\Support\Entitlements\EntitlementHooks;
use Modules\Hr\Support\Inbox\DeskAgenda;
use Modules\Hr\Support\Inbox\HrSubjectLinks;
use Modules\Hr\Support\Inbox\InboxWorkItems;
use Modules\Hr\Support\Journeys\JourneyHooks;
use Modules\Hr\Support\MailLists\MailListSync;
use Modules\Hr\Support\Metrics\HrMetrics;
use Modules\Hr\Support\Pickers\HrPickerScopes;
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
use Modules\Hr\Support\Search\ContractSearchSource;
use Modules\Hr\Support\Search\EmployeeSearchSource;
use Modules\Hr\Support\Search\LeaveRequestSearchSource;
use Modules\Hr\Support\Search\MailListSearchSource;
use Modules\Hr\Support\Search\PositionSearchSource;
use Modules\Hr\Support\Search\RecruitmentRequestSearchSource;
use Modules\Hr\Support\Timeline\ContractsTimelineSource;
use Modules\Hr\Support\Timeline\EquipmentTimelineSource;
use Modules\Hr\Support\Timeline\JourneysTimelineSource;
use Throwable;

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

        // Critical positions: the AI re-ranking layer is a no-op unless a real one is bound.
        $this->app->bind(CandidateAiRanker::class, NullCandidateAiRanker::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/resources/views', 'hr');
        Livewire::addNamespace('hr', viewPath: __DIR__.'/resources/views/pages');
        $this->registerCoreBridge();
        $this->registerChat();

        $this->commands([
            ImportHrCommand::class,
            CopyHrFilesCommand::class,
            ApplyContractAmendmentsCommand::class,
            BackfillEntitlementsCommand::class,
            CheckContractAmendmentsCommand::class,
            SendOrientationReminders::class,
            SendOnboardingStepReminders::class,
            SendContractExpirationWarnings::class,
            TrackRenewalJourneys::class,
            DeactivateDepartedEmployees::class,
            DeactivateExpiredContractsCommand::class,
            ProcessContractAutoRenewalsCommand::class,
            RemindMissingLeavingReasonsCommand::class,
            StartProbationReviewsCommand::class,
            SendLeaveDecisionRemindersCommand::class,
            RecountPositionSeatsCommand::class,
            SendAttendanceRemindersCommand::class,
            LoadMailListsCommand::class,
            QueueMailListStartersCommand::class,
            NotifyBirthdayConflictsCommand::class,
            NotifyCriticalPositionsCommand::class,
            PurgeUploadedPunchesCommand::class,
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

        // Hub unification reports (read only).
        $this->commands([ReportAccountGapsCommand::class, ReportRecruitmentAccessCommand::class]);

        // The move of hr_media into the encrypted media store: dry run, --copy, --verify (never deletes).
        $this->commands([MoveHrMediaCommand::class]);

        // HR's background jobs and their times: Modules\Hr\Console\HrSchedule.
        $this->app->make(ModuleRegistry::class)->addSchedule(HrSchedule::class);

        // Rate limiters, observers and bindings; outside the routes so it also runs under route:cache.
        RecruitmentBootstrap::boot();

        // A time-clock device calls about every 30 seconds and posts its log in bursts: generous per
        // device, and by address when it does not say who it is.
        RateLimiter::for('iclock', fn (Request $request): Limit => Limit::perMinute(300)
            ->by((string) ($request->query('SN') ?? $request->query('sn') ?? $request->ip())));

        // The attendance agent calls every ten minutes, in a few batches. Per key (or per address when it sends none).
        RateLimiter::for('attendance-agent', fn (Request $request): Limit => Limit::perMinute(60)
            ->by(sha1((string) ($request->bearerToken() ?: $request->ip()))));

        // Contract writes that start or move an employee journey.
        JourneyHooks::register();

        // Contract writes that claim a seat bring the position's Permissions Library defaults.
        EntitlementHooks::register();

        // A new account on the organisation's domain waits to be added to the all-staff mail list.
        User::created(function (User $user): void {
            try {
                MailList::queueNewAccount($user);
            } catch (Throwable $exception) {
                report($exception);
            }
        });

        $this->registerMailListHooks();

        $this->registerDesk();

        if (! $this->app->routesAreCached()) {
            $this->loadAreaRoutes();
        }
    }

    /**
     * The rest of a person's lists follow by the rules of the lists (MailListSync): a hire (an
     * account or a contract) queues them as pending on the lists they belong on, and leaving (the
     * employee turned inactive) flags them for removal at the control panel. Nothing here may stop
     * a hire or a departure.
     */
    private function registerMailListHooks(): void
    {
        $guarded = static fn (callable $work): Closure => static function (Model $model) use ($work): void {
            try {
                $work($model, app(MailListSync::class));
            } catch (Throwable $exception) {
                report($exception);
            }
        };

        User::created($guarded(function (User $user, MailListSync $sync): void {
            $employee = $user->employee_id === null ? null : Employee::query()->find($user->employee_id);

            if ($employee !== null) {
                $sync->queueForPerson($employee, auth()->id());
            }
        }));

        // A bulk import or command would queue hundreds at once: the check page does that on demand.
        Contract::created($guarded(function (Contract $contract, MailListSync $sync): void {
            if ($this->app->runningInConsole() || ! $contract->is_active) {
                return;
            }

            $employee = Employee::query()->find($contract->employee_id);

            if ($employee !== null) {
                $sync->queueForPerson($employee, auth()->id());
            }
        }));

        Employee::updated($guarded(function (Employee $employee, MailListSync $sync): void {
            if (! $employee->wasChanged('is_active')) {
                return;
            }

            $employee->is_active ? $sync->unflagLeaving($employee) : $sync->flagLeaving($employee);
        }));
    }

    /**
     * HR's sidebar pages and Desk cards, read by App\Support\Desk\Destinations and Panels through the module registry.
     */
    private function registerDesk(): void
    {
        $modules = $this->app->make(ModuleRegistry::class);
        $modules->addDestinations(HrDestinations::class);
        $modules->addDeskPanels(HrPanels::class);
        $modules->addDeskAgenda(DeskAgenda::class);
        $modules->addSubjectLinks(HrSubjectLinks::class);
        $modules->addCalendarLayer(HolidayCalendarLayer::class);
        $modules->addCalendarLayer(LeaveCalendarLayer::class);
        $modules->addTimeline(ContractsTimelineSource::class);
        $modules->addTimeline(EquipmentTimelineSource::class);
        $modules->addTimeline(JourneysTimelineSource::class);
        $modules->addSearch(EmployeeSearchSource::class);
        $modules->addSearch(PositionSearchSource::class);
        $modules->addSearch(ContractSearchSource::class);
        $modules->addSearch(LeaveRequestSearchSource::class);
        $modules->addSearch(RecruitmentRequestSearchSource::class);
        $modules->addSearch(MailListSearchSource::class);
        $modules->addMetrics(HrMetrics::class);
        $modules->addWorkItems(InboxWorkItems::class);
        $modules->addPickerScopes(HrPickerScopes::class);
    }

    /**
     * The in-site chat (config/chat.php, off until CHAT_ENABLED): its Livewire
     * components, used in the app frame as <livewire:hr.chat.dock />, and its
     * realtime channels. Its pages are routes/chat.php.
     */
    private function registerChat(): void
    {
        Livewire::component('hr.chat.dock', ChatDock::class);
        Livewire::component('hr.chat.window', ChatWindow::class);
        Livewire::component('hr.chat.header-button', ChatHeaderButton::class);
        Livewire::component('hr.chat.page', ChatPage::class);

        require __DIR__.'/routes/broadcast/chat-channels.php';
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

        // The time-clock devices (ZKTeco ADMS): no session, no login, no language prefix; they are
        // believed by their registered serial only. routes/device/iclock.php.
        Route::middleware(['api', 'throttle:iclock'])->group(__DIR__.'/routes/device/iclock.php');

        // The LAN agent that reads a device on its own network: believed by a per-device sync key.
        Route::middleware(['api', 'throttle:attendance-agent'])->group(__DIR__.'/routes/device/agent.php');

        // Pages a guest may open (the careers site): routes/public/*.php.
        $publicFiles = glob(__DIR__.'/routes/public/*.php') ?: [];

        Route::middleware('web')->group(function () use ($publicFiles): void {
            foreach ($publicFiles as $file) {
                require $file;
            }
        });
    }

    /**
     * HR's side of the Core models, kept here so Core does not depend on HR
     * (D10): the relations Core models used to declare, under the same names
     * and keys, and the employee lookup User reads through.
     */
    private function registerCoreBridge(): void
    {
        $this->app->singleton(EmployeeLookup::class, HrEmployeeLookup::class);

        User::resolveRelationUsing('employee', fn (User $user): BelongsTo => $user->belongsTo(Employee::class, 'employee_id', 'id', 'employee'));
        Program::resolveRelationUsing('manager', fn (Program $program): BelongsTo => $program->belongsTo(Employee::class, 'manager_id', 'id', 'manager'));
        City::resolveRelationUsing('positions', fn (City $city): HasMany => $city->hasMany(Position::class));
        City::resolveRelationUsing('employees', fn (City $city): HasMany => $city->hasMany(Employee::class));
        Center::resolveRelationUsing('positions', fn (Center $center): HasMany => $center->hasMany(Position::class));
        Center::resolveRelationUsing('contracts', fn (Center $center): HasManyThrough => $center->hasManyThrough(Contract::class, Position::class));
    }
}
