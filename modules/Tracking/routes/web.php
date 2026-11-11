<?php

use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use Illuminate\Support\Facades\Route;
use Modules\Tracking\Http\Controllers\DonorReportExportController;
use Modules\Tracking\Http\Controllers\DonorReportFileDownloadController;
use Modules\Tracking\Http\Controllers\DonorReportPrintController;
use Modules\Tracking\Http\Controllers\MonthlyReportFileDownloadController;
use Modules\Tracking\Http\Controllers\ProjectFileDownloadController;
use Modules\Tracking\Http\Controllers\ProposalFileDownloadController;
use Modules\Tracking\Http\Controllers\ProposalFilePreviewController;
use Modules\Tracking\Http\Controllers\ReportFileDownloadController;
use Modules\Tracking\Http\Controllers\ReportFilePreviewController;
use Modules\Tracking\Http\Controllers\TaskFileDownloadController;
use Modules\Tracking\Http\Controllers\TaskFilePreviewController;
use Modules\Tracking\Http\Middleware\RedirectProposalToStory;
use Modules\Tracking\Models\DonorReport;
use Modules\Tracking\Models\RiskLibrary;
use Modules\Tracking\Models\Task;

/*
| Tracking pages. Loaded by TrackingServiceProvider inside the `web` group;
| the URLs and route names are the ones routes/web.php used to declare.
*/
Route::middleware(['auth', EnsureTwoFactorIsEnabled::class])->group(function () {
    // Open to everyone: the page lists only the viewer's own reviewer rows.
    Route::livewire('my-reviews', 'tracking::reviews.index')->name('reviews.index');

    Route::livewire('calendar', 'tracking::calendar.index')
        ->can('calendar.view')
        ->name('calendar.index');

    Route::livewire('strategies', 'tracking::strategies.index')
        ->can('strategies.view')
        ->name('strategies.index');

    Route::livewire('strategies/{strategy}', 'tracking::strategies.show')
        ->can('strategies.view')
        ->name('strategies.show');

    Route::livewire('programs', 'tracking::programs.index')
        ->can('programs.view')
        ->name('programs.index');

    Route::livewire('programs/{program}', 'tracking::programs.show')
        ->can('programs.view')
        ->name('programs.show');

    Route::livewire('annual-plans', 'tracking::annual-plans.index')
        ->can('annual-plans.view')
        ->name('annual-plans.index');

    Route::livewire('annual-plans/{annualPlan}', 'tracking::annual-plans.show')
        ->can('annual-plans.view')
        ->name('annual-plans.show');

    Route::livewire('projects', 'tracking::projects.index')
        ->can('projects.view')
        ->name('projects.index');

    Route::livewire('projects/{project}', 'tracking::projects.show')
        ->can('projects.view')
        ->name('projects.show');

    Route::get('projects/{project}/files/{media}/download', ProjectFileDownloadController::class)
        ->can('projects.view')
        ->middleware('throttle:files')
        ->name('projects.files.download');

    Route::livewire('risks', 'tracking::risks.index')
        ->can('risks.view')
        ->name('risks.index');

    Route::livewire('milestones', 'tracking::milestones.index')
        ->can('milestones.view')
        ->name('milestones.index');

    Route::livewire('milestones/{milestone}', 'tracking::milestones.show')
        ->can('milestones.view')
        ->name('milestones.show');

    Route::livewire('results', 'tracking::results.index')
        ->can('results.view')
        ->name('results.index');

    Route::livewire('indicator-library', 'tracking::indicator-library.index')
        ->can('indicator-library.view')
        ->name('indicator-library.index');

    Route::livewire('indicators', 'tracking::indicators.index')
        ->can('indicators.view')
        ->name('indicators.index');

    Route::livewire('targets', 'tracking::targets.index')
        ->can('targets.view')
        ->name('targets.index');

    Route::livewire('tasks', 'tracking::tasks.index')
        ->can('tasks.view')
        ->name('tasks.index');

    Route::livewire('department-tasks', 'tracking::department-tasks.index')
        ->can('viewDepartmentQueue', Task::class)
        ->name('department-tasks.index');

    Route::livewire('tasks/{task}', 'tracking::tasks.show')
        ->can('tasks.view')
        ->name('tasks.show');

    Route::get('tasks/{task}/files/{taskAttachment}/download', TaskFileDownloadController::class)
        ->can('tasks.view')
        ->middleware('throttle:files')
        ->name('tasks.files.download');

    Route::get('tasks/{task}/files/{taskAttachment}/preview', TaskFilePreviewController::class)
        ->can('tasks.view')
        ->middleware('throttle:files')
        ->name('tasks.files.preview');

    Route::livewire('proposals', 'tracking::proposals.index')
        ->can('proposals.view')
        ->name('proposals.index');

    Route::livewire('proposals/{proposal}', 'tracking::proposals.show')
        ->can('view', 'proposal')
        ->middleware(RedirectProposalToStory::class)
        ->name('proposals.show');

    Route::get('proposals/{proposal}/files/{media}/download', ProposalFileDownloadController::class)
        ->can('view', 'proposal')
        ->middleware('throttle:files')
        ->name('proposals.files.download');

    Route::get('proposals/{proposal}/files/{media}/preview', ProposalFilePreviewController::class)
        ->can('view', 'proposal')
        ->middleware('throttle:files')
        ->name('proposals.files.preview');

    Route::livewire('donors', 'tracking::donors.index')
        ->can('donors.view')
        ->name('donors.index');

    Route::livewire('donors/{donor}', 'tracking::donors.show')
        ->can('donors.view')
        ->name('donors.show');

    Route::livewire('funding-agreements', 'tracking::funding-agreements.index')
        ->can('funding-agreements.view')
        ->name('funding-agreements.index');

    Route::livewire('funding-agreements/{donorProposal}', 'tracking::funding-agreements.show')
        ->can('funding-agreements.view')
        ->name('funding-agreements.show');

    Route::livewire('donor-reports', 'tracking::donor-reports.index')
        ->can('viewAny', DonorReport::class)
        ->name('donor-reports.index');

    Route::livewire('donor-reports/compose', 'tracking::donor-reports.compose')
        ->can('reports.aggregate')
        ->name('donor-reports.compose');

    Route::livewire('donor-reports/{donorReport}', 'tracking::donor-reports.show')
        ->whereNumber('donorReport')
        ->can('view', 'donorReport')
        ->name('donor-reports.show');

    Route::get('donor-reports/{donorReport}/print', DonorReportPrintController::class)
        ->whereNumber('donorReport')
        ->can('view', 'donorReport')
        ->middleware('throttle:files')
        ->name('donor-reports.print');

    Route::get('donor-reports/{donorReport}/export', DonorReportExportController::class)
        ->whereNumber('donorReport')
        ->can('view', 'donorReport')
        ->middleware('throttle:files')
        ->name('donor-reports.export');

    Route::get('donor-reports/{donorReport}/files/{media}/download', DonorReportFileDownloadController::class)
        ->whereNumber(['donorReport', 'media'])
        ->middleware('throttle:files')
        ->name('donor-reports.files.download');

    // Open to everyone: the page lists only the reports the viewer is responsible for.
    Route::livewire('reporting-duties', 'tracking::reporting-duties.index')
        ->name('reporting-duties.index');

    Route::livewire('transfers', 'tracking::transfers.index')
        ->can('transfers.view')
        ->name('transfers.index');

    Route::livewire('reports', 'tracking::reports.index')
        ->can('reports.view')
        ->name('reports.index');

    Route::livewire('reports/monthly-matrix', 'tracking::reports.monthly-matrix')
        ->can('reports.view')
        ->name('reports.monthly-matrix');

    Route::livewire('reports/{report}', 'tracking::reports.show')
        ->can('reports.view')
        ->name('reports.show');

    Route::livewire('monthly-reports', 'tracking::monthly-reports.index')
        ->can('reports.view')
        ->name('monthly-reports.index');

    // The page authorizes MonthlyReportPolicy::view() in mount().
    Route::livewire('monthly-reports/{monthlyReport}', 'tracking::monthly-reports.show')
        ->can('reports.view')
        ->name('monthly-reports.show');

    // The file loads through $monthlyReport->files() (contracts §13.1 item 4).
    Route::get('monthly-reports/{monthlyReport}/files/{monthlyReportFile}/download', MonthlyReportFileDownloadController::class)
        ->whereNumber('monthlyReportFile')
        ->can('reports.view')
        ->middleware('throttle:files')
        ->name('monthly-reports.files.download');

    Route::get('reports/{report}/files/{reportFile}/download', ReportFileDownloadController::class)
        ->can('reports.view')
        ->middleware('throttle:files')
        ->name('reports.files.download');

    Route::get('reports/{report}/files/{reportFile}/preview', ReportFilePreviewController::class)
        ->can('reports.view')
        ->middleware('throttle:files')
        ->name('reports.files.preview');

    Route::livewire('admin/cities', 'tracking::admin.cities')
        ->can('cities.view')
        ->name('admin.cities.index');

    Route::livewire('admin/sectors', 'tracking::admin.sectors')
        ->can('sectors.view')
        ->name('admin.sectors.index');

    Route::livewire('admin/risk-library', 'tracking::admin.risk-library')
        ->can('viewAny', RiskLibrary::class)
        ->name('admin.risk-library.index');

    Route::livewire('admin/evidence-types', 'tracking::admin.evidence-types')
        ->can('evidence-types.manage')
        ->name('admin.evidence-types.index');

    Route::livewire('admin/narrative-questions', 'tracking::admin.narrative-questions')
        ->can('narrative-questions.manage')
        ->name('admin.narrative-questions.index');

    Route::livewire('report-templates', 'tracking::report-templates.index')
        ->can('report-templates.manage')
        ->name('report-templates.index');

    Route::livewire('admin/accounts', 'tracking::admin.accounts')
        ->can('accounts.view')
        ->name('admin.accounts.index');
});
