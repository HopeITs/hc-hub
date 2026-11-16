<?php

/**
 * Module boundary allow-lists. SHRINK ONLY.
 *
 * Each rule says "classes under `source` must not use anything under `forbidden`"
 * (docs/hub-unification/02-target-design.md §1, 03-roadmap.md §5.4).
 * `allowed` holds the classes that broke the rule when the arch tests were added.
 *
 * - Never add a class here. A new forbidden import must be fixed, not listed.
 * - When you fix a class, delete its line and lower `max` to the new count.
 *   tests/Arch/ModuleBoundariesTest.php fails while a listed class no longer
 *   breaks the rule, so a fixed entry cannot linger.
 * - `max` is pinned: the list may never be longer than it.
 *
 * @return array<string, array{source: string, forbidden: string, max: int, allowed: list<class-string>}>
 */
return [
    // Core is the shared base. Clean since 1.4: no exceptions.
    'Modules\Core must not use Modules\Hr' => [
        'source' => 'Modules\Core',
        'forbidden' => 'Modules\Hr',
        'max' => 0,
        'allowed' => [],
    ],
    // Core is the shared base. Clean since 1.4: no exceptions.
    'Modules\Core must not use Modules\Tracking' => [
        'source' => 'Modules\Core',
        'forbidden' => 'Modules\Tracking',
        'max' => 0,
        'allowed' => [],
    ],
    // HR sits below Tracking. Clean since 1.3: no exceptions.
    'Modules\Hr must not use Modules\Tracking' => [
        'source' => 'Modules\Hr',
        'forbidden' => 'Modules\Tracking',
        'max' => 0,
        'allowed' => [],
    ],
    // Frozen baseline: new Tracking code must not reach into HR directly.
    // One sanctioned addition (1.3): TrackingServiceProvider registers the
    // Tracking relations on Employee (02-target-design §1), which is how the
    // Hr -> Tracking cycle edge was removed. Net count across rules went down.
    'Modules\Tracking must not use Modules\Hr' => [
        'source' => 'Modules\Tracking',
        'forbidden' => 'Modules\Hr',
        'max' => 52,
        'allowed' => [
            'Modules\Tracking\TrackingServiceProvider',
            'Modules\Tracking\Actions\Cities\AssignMealCoverage',
            'Modules\Tracking\Actions\DonorReports\AssignDonorReportResponsible',
            'Modules\Tracking\Actions\DonorReports\SaveDonorReportSnapshot',
            'Modules\Tracking\Actions\Milestones\SaveProposalMilestone',
            'Modules\Tracking\Actions\Projects\SaveProject',
            'Modules\Tracking\Actions\Proposals\AddProposalReviewer',
            'Modules\Tracking\Actions\Proposals\CloneProposalForRenewal',
            'Modules\Tracking\Actions\Proposals\SaveDefaultReviewerSlots',
            'Modules\Tracking\Actions\Proposals\SaveProposal',
            'Modules\Tracking\Actions\Supervisions\AddSupervision',
            'Modules\Tracking\Actions\Tasks\SaveDepartmentTask',
            'Modules\Tracking\Console\Commands\MapProjectCenters',
            'Modules\Tracking\Livewire\Concerns\AddsQuickTask',
            'Modules\Tracking\Livewire\Concerns\Admin\ManagesMealCoverage',
            'Modules\Tracking\Livewire\Concerns\AnnualPlans\FiltersPlanMatrix',
            'Modules\Tracking\Livewire\Concerns\AnnualPlans\ManagesAnnualPlanForm',
            'Modules\Tracking\Livewire\Concerns\BuildsPortfolioGantt',
            'Modules\Tracking\Livewire\Concerns\BuildsProjectGantt',
            'Modules\Tracking\Livewire\Concerns\Calendar\ManagesCalendarMilestones',
            'Modules\Tracking\Livewire\Concerns\Calendar\ManagesCalendarTasks',
            'Modules\Tracking\Livewire\Concerns\Calendar\ScopesCalendarPeople',
            'Modules\Tracking\Livewire\Concerns\FundingAgreements\ReadsAgreementConnections',
            'Modules\Tracking\Livewire\Concerns\ManagesTaskVisibility',
            'Modules\Tracking\Livewire\Concerns\Milestones\ManagesMilestoneDirectoryForm',
            'Modules\Tracking\Livewire\Concerns\Programs\LinksProgram',
            'Modules\Tracking\Livewire\Concerns\Programs\ManagesProgramTasks',
            'Modules\Tracking\Livewire\Concerns\Projects\FiltersProjectDirectory',
            'Modules\Tracking\Livewire\Concerns\Projects\ManagesProjectForm',
            'Modules\Tracking\Livewire\Concerns\Projects\ReadsProjectStory',
            'Modules\Tracking\Livewire\Concerns\Proposals\ManagesProposalPlan',
            'Modules\Tracking\Livewire\Concerns\Proposals\ManagesProposalReviewers',
            'Modules\Tracking\Livewire\Concerns\Proposals\ManagesProposalStatus',
            'Modules\Tracking\Livewire\Concerns\SavesTaskForm',
            'Modules\Tracking\Livewire\Concerns\Tasks\ComposesInlineTasks',
            'Modules\Tracking\Livewire\Concerns\Tasks\FiltersTaskDirectory',
            'Modules\Tracking\Livewire\Concerns\Tasks\ManagesSubtasks',
            'Modules\Tracking\Livewire\Concerns\Tasks\ManagesTaskDrawer',
            'Modules\Tracking\Livewire\Concerns\Tasks\ProvidesTaskOptions',
            'Modules\Tracking\Models\AnnualPlan',
            'Modules\Tracking\Models\Assignment',
            'Modules\Tracking\Models\Milestone',
            'Modules\Tracking\Models\Project',
            'Modules\Tracking\Models\TaskAssignee',
            'Modules\Tracking\Notifications\Alerts\ResponsibleInactiveScanner',
            'Modules\Tracking\Notifications\Recipients\ProjectRecipients',
            'Modules\Tracking\Notifications\Recipients\TaskRecipients',
            'Modules\Tracking\Policies\AssignmentPolicy',
            'Modules\Tracking\Support\DonorReportResponsibles',
            'Modules\Tracking\Support\MealCoverage',
            'Modules\Tracking\Support\ResultsChain',
            'Modules\Tracking\Support\SupervisionMap',
        ],
    ],
    // The platform reaches modules through contracts (D10 registry).
    'App must not use Modules\Hr' => [
        'source' => 'App',
        'forbidden' => 'Modules\Hr',
        'max' => 11,
        'allowed' => [
            'App\Http\Controllers\EmployeeAvatarController',
            'App\Http\Controllers\EmployeePickerOptionsController',
            'App\Livewire\Concerns\Dashboard\ReadsDashboardWork',
            'App\Livewire\Concerns\Dashboard\ReadsDeskDecisions',
            'App\Notifications\Alerts\DeadlineCatalog',
            'App\Notifications\EmployeeRecipients',
            'App\Notifications\QuickActions',
            'App\Providers\AppServiceProvider',
            'App\Support\Desk\ReadyLayoutSignals',
            'App\Support\EmployeeProfile',
            'App\Support\HrHolidays',
        ],
    ],
    // The platform reaches modules through contracts (D10 registry).
    'App must not use Modules\Tracking' => [
        'source' => 'App',
        'forbidden' => 'Modules\Tracking',
        'max' => 47,
        'allowed' => [
            'App\Console\Commands\PruneStoredFiles',
            'App\Console\Commands\ReencryptSecrets',
            'App\Http\Middleware\KeepDeskTabOnRedirect',
            'App\Livewire\Concerns\Dashboard\ReadsDashboardAttention',
            'App\Livewire\Concerns\Dashboard\ReadsDashboardWork',
            'App\Livewire\Concerns\Dashboard\ReadsDeskDecisions',
            'App\Livewire\Concerns\Desk\ReadsDeskAgenda',
            'App\Livewire\Concerns\Desk\ReadsDeskFlow',
            'App\Notifications\ActivityParameters',
            'App\Notifications\Alerts\DailyBriefScanner',
            'App\Notifications\Alerts\DeadlineCatalog',
            'App\Notifications\Alerts\MonthlyDigestScanner',
            'App\Notifications\Alerts\WeeklyDigestScanner',
            'App\Notifications\Alerts\WorkingCalendar',
            'App\Notifications\EventCatalog',
            'App\Notifications\QuickActions',
            'App\Notifications\Recipients\OversightRecipients',
            'App\Notifications\SubjectLinks',
            'App\Observers\MediaOwnerObserver',
            'App\Providers\AppServiceProvider',
            'App\Support\Desk\Cards\AccountsCard',
            'App\Support\Desk\Cards\AgreementsCard',
            'App\Support\Desk\Cards\AnnualPlansCard',
            'App\Support\Desk\Cards\CitiesCard',
            'App\Support\Desk\Cards\DonorReportsCard',
            'App\Support\Desk\Cards\EvidenceTypesCard',
            'App\Support\Desk\Cards\IndicatorsCard',
            'App\Support\Desk\Cards\LibraryCard',
            'App\Support\Desk\Cards\NarrativeQuestionsCard',
            'App\Support\Desk\Cards\ProgramsCard',
            'App\Support\Desk\Cards\ReportsCard',
            'App\Support\Desk\Cards\ResultsCard',
            'App\Support\Desk\Cards\RiskLibraryCard',
            'App\Support\Desk\Cards\SectorsCard',
            'App\Support\Desk\Cards\StrategiesCard',
            'App\Support\Desk\DeskWork',
            'App\Support\Desk\Destinations',
            'App\Support\Desk\FundingTotals',
            'App\Support\Desk\Panels',
            'App\Support\Desk\ProjectStage',
            'App\Support\Desk\ReadyLayoutSignals',
            'App\Support\Desk\Shortcuts',
            'App\Support\Desk\TeamScope',
            'App\Support\FluxMarkup',
            'App\Support\HrHolidays',
            'App\Support\Slots',
            'App\Support\StoredFiles',
        ],
    ],
];
