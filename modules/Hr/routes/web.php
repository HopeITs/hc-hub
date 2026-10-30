<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\ArchiveFileController;
use Modules\Hr\Http\Controllers\EmployeeAvatarController;
use Modules\Hr\Http\Controllers\EmployeeDocumentController;
use Modules\Hr\Livewire\Archives\ArchivedFilesModule;
use Modules\Hr\Livewire\Badges\BadgesModule;
use Modules\Hr\Livewire\Basics\CourseCategoryModule;
use Modules\Hr\Livewire\ChecklistItems\AllItems;
use Modules\Hr\Livewire\ChecklistItems\DoneRequests;
use Modules\Hr\Livewire\ChecklistItems\PendingItems;
use Modules\Hr\Livewire\ChecklistItems\PendingReturns;
use Modules\Hr\Livewire\Employee\EmployeeProfile;
use Modules\Hr\Livewire\Employee\EmployeeStatistics;
use Modules\Hr\Livewire\Employee\Leave\AllLeaveRequestModule;
use Modules\Hr\Livewire\Employee\Leave\EmpLeaveBalanceModule;
use Modules\Hr\Livewire\Employee\Leave\MyLeaveRequestModule;
use Modules\Hr\Livewire\Inbox\Index as InboxIndex;
use Modules\Hr\Livewire\News\NewsCreate;
use Modules\Hr\Livewire\News\NewsModule;
use Modules\Hr\Livewire\Statistics\ContractStatistics;
use Modules\Hr\Livewire\Statistics\GeneralStatistics;
use Modules\Hr\Livewire\Team\Calendar as TeamCalendar;

// HR routes. Required from routes/web.php inside the locale group and the
// 'auth' middleware group, so every path here is /{locale}/... and signed in.

Route::prefix('statistics')
    ->name('statistics.')
    ->middleware('active_permission:hr.statistics.view')
    ->group(function () {
        Route::get('/general', GeneralStatistics::class)->name('general');
        Route::get('/contract', ContractStatistics::class)->name('contract');
    });

Route::get('my-profile', EmployeeProfile::class)->name('my-profile');
// C3: the work inbox. No permission of its own: every row comes from a
// source's scoped query and every action re-checks its domain policy.
Route::get('inbox', InboxIndex::class)->name('inbox');

// Basic project routes
require __DIR__.'/basic-project.php';

require __DIR__.'/projects-management.php';

require __DIR__.'/employees-management.php';

// C5: who is away, for everyone; TeamCoverage limits what each role sees.
Route::get('/team-calendar', TeamCalendar::class)->name('team.calendar')->middleware('active_permission:hr.leaves.view-own');

Route::prefix('leaves')
    ->name('leaves.')
    ->group(function () {
        Route::get('/', MyLeaveRequestModule::class)->middleware('active_permission:hr.leaves.view-own')->name('index');
        Route::get('/all', AllLeaveRequestModule::class)->name('all')->middleware('active_permission:hr.leaves.view-all');
        Route::get('/balance', EmpLeaveBalanceModule::class)->name('balance')->middleware('active_permission:hr.leaves.manage-balances');
    });

Route::view('archived-files/{folder?}', 'archives.index')->middleware('active_permission:hr.archives.view')->name('archived-files.index');
Route::get('my-folders/{folderId?}', ArchivedFilesModule::class)->defaults('isShared', false)->middleware('active_permission:hr.archives.view')->name('my-folders.index');
Route::get('shared-folders/{folderId?}', ArchivedFilesModule::class)->defaults('isShared', true)->middleware('active_permission:hr.archives.view')->name('shared-folders.index');
Route::get('folder-files/{media}/preview', [ArchiveFileController::class, 'preview'])->middleware('active_permission:hr.archives.view')->name('folder-files.preview');
Route::get('folder-files/{media}/download', [ArchiveFileController::class, 'download'])->middleware('active_permission:hr.archives.download')->name('folder-files.download');
Route::get('folder-files/{media}/office-preview', [ArchiveFileController::class, 'officePreview'])->middleware('active_permission:hr.archives.view')->name('folder-files.office-preview');
Route::get('employee-files/{media}/download', [EmployeeDocumentController::class, 'download'])
    ->middleware('throttle:60,1')
    ->name('employee-files.download');

// Employee photographs live on the private disk; every in-app
// <img> resolves through here so EmployeePolicy (and with it the
// city scope) applies to the image itself, not just to the page
// that embeds it.
Route::get('employee-photo/{employee}', [EmployeeAvatarController::class, 'internal'])
    ->name('employees.avatar');

Route::prefix('checklist-items')
    ->name('checklist-items.')
    ->group(function () {
        Route::get('/pending-delivers', PendingItems::class)->middleware('active_permission:hr.checklists.deliver')->name('pending-delivers');
        Route::get('/pending-returns', PendingReturns::class)->middleware('active_permission:hr.checklists.receive')->name('pending-returns');
        Route::get('/done-requests', DoneRequests::class)->middleware('active_permission:hr.checklists.manage')->name('done-requests');
        Route::get('/all', AllItems::class)->middleware('active_permission:hr.checklists.view-own')->name('index');
    });

Route::get('/news', NewsModule::class)->name('news.index')->middleware('active_permission:hr.news.view');

Route::get('/employee-statistics/{mode}', [EmployeeStatistics::class, 'getStatistics'])
    ->middleware('active_permission:hr.statistics.view');

Route::get('/badges', BadgesModule::class)
    ->middleware('active_permission:hr.badges.view')
    ->name('badges.index');

Route::get('/news/create', NewsCreate::class)->name('news.create')->middleware('active_permission:hr.news.create');
Route::get('/news/{news}/edit', NewsCreate::class)->name('news.edit')->middleware('active_permission:hr.news.update');

Route::get('/course-categories', CourseCategoryModule::class)->middleware('reference_page:project-basics')->name('course-categories.index');
