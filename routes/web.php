<?php

use App\Http\Controllers\ArchiveFileController;
use App\Livewire\ActivityLog\ActivityLogModule;
use App\Livewire\Archives\ArchivedFilesModule;
use App\Livewire\Badges\BadgesModule;
use App\Livewire\Basics\CourseCategoryModule;
use App\Livewire\ChecklistItems\AllItems;
use App\Livewire\ChecklistItems\DoneRequests;
use App\Livewire\ChecklistItems\PendingItems;
use App\Livewire\ChecklistItems\PendingReturns;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Employee\EmployeeProfile;
use App\Livewire\Employee\EmployeeStatistics;
use App\Livewire\Employee\Leave\AllLeaveRequestModule;
use App\Livewire\Employee\Leave\EmpLeaveBalanceModule;
use App\Livewire\Employee\Leave\MyLeaveRequestModule;
use App\Livewire\EmployeeCards\ShowDigitalId;
use App\Livewire\News\NewsCreate;
use App\Livewire\News\NewsModule;
use App\Livewire\Notifications\Index;
use App\Livewire\Settings\Password;
use App\Livewire\Statistics\ContractStatistics;
use App\Livewire\Statistics\GeneralStatistics;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::get('/ID/{username}', ShowDigitalId::class)->name('digital-id.show');

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    function () {
        Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle));

        Route::middleware(['auth'])->group(function () {
            Route::get('/', DashboardIndex::class)
                ->name('dashboard');
            Route::prefix('statistics')
                ->name('statistics.')
                ->middleware('role:HR|super-admin')
                ->group(function () {
                    Route::get('/general', GeneralStatistics::class)->name('general');
                    Route::get('/contract', ContractStatistics::class)->name('contract');
                });

            Route::redirect('settings', 'settings/password');
            Route::get('settings/password', Password::class)->name('settings.password');
            Route::get('my-profile', EmployeeProfile::class)->name('my-profile');

            // Basic project routes
            require __DIR__.'/basic-project.php';

            require __DIR__.'/projects-management.php';

            require __DIR__.'/employees-management.php';

            Route::prefix('leaves')
                ->name('leaves.')
                ->group(function () {
                    Route::get('/', MyLeaveRequestModule::class)->name('index');
                    Route::get('/all', AllLeaveRequestModule::class)->name('all')->middleware('role:HR|super-admin|manager|general-manager');
                    Route::get('/balance', EmpLeaveBalanceModule::class)->name('balance')->middleware('role:HR|super-admin|general-manager');
                });

            Route::view('archived-files/{folder?}', 'archives.index')->name('archived-files.index');
            Route::get('my-folders/{folderId?}', ArchivedFilesModule::class)->defaults('isShared', false)->name('my-folders.index');
            Route::get('shared-folders/{folderId?}', ArchivedFilesModule::class)->defaults('isShared', true)->name('shared-folders.index');
            Route::get('folder-files/{media}/preview', [ArchiveFileController::class, 'preview'])->name('folder-files.preview');
            Route::get('folder-files/{media}/download', [ArchiveFileController::class, 'download'])->name('folder-files.download');
            Route::get('folder-files/{media}/office-preview', [ArchiveFileController::class, 'officePreview'])->name('folder-files.office-preview');

            Route::prefix('checklist-items')
                ->name('checklist-items.')
                ->group(function () {
                    Route::get('/pending-delivers', PendingItems::class)->name('pending-delivers');
                    Route::get('/pending-returns', PendingReturns::class)->name('pending-returns');
                    Route::get('/done-requests', DoneRequests::class)->name('done-requests');
                    Route::get('/all', AllItems::class)->name('index');
                });

            Route::get('/notifications', Index::class)->name('notifications.index');

            Route::get('/news', NewsModule::class)->name('news.index')->middleware('role:HR|super-admin|general-manager|manager');

            Route::get('/employee-statistics/{mode}', [EmployeeStatistics::class, 'getStatistics']);

            Route::get('/badges', BadgesModule::class)->name('badges.index');

            Route::get('/news/create', NewsCreate::class)->name('news.create')->middleware('role:HR|super-admin|general-manager|manager');
            Route::get('/news/{news}/edit', NewsCreate::class)->name('news.edit')->middleware('role:HR|super-admin|general-manager|manager');

            Route::get('/course-categories', CourseCategoryModule::class)->name('course-categories.index');
            Route::get('/activity-log', ActivityLogModule::class)
                ->name('activity-log.index')
                ->middleware('role:HR|super-admin');

        });

        require __DIR__.'/auth.php';
    },
);
