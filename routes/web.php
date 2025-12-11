<?php


use App\Livewire\Archives\ArchivedFilesModule;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\ChecklistItems\{AllItems, DoneRequests, PendingItems, PendingReturns};

use App\Livewire\Statistics\ContractStatistics;
use App\Livewire\Statistics\GeneralStatistics;
use App\Livewire\Employee\{
    Leave\AllLeaveRequestModule,  Leave\EmpLeaveBalanceModule, Leave\MyLeaveRequestModule, Leave\MyLeaveRequestTable};
use App\Livewire\Employee\EmployeeStatistics;
use App\Livewire\Settings\{Appearance, Password, Profile};
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use App\Livewire\Basics\CourseCategoryModule;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
    ],
    function () {
        Livewire::setUpdateRoute(fn($handle) => Route::post('/livewire/update', $handle));


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


            //Basic project routes
            require __DIR__ . '/basic-project.php';

            require __DIR__ . '/projects-management.php';

            require __DIR__ . '/employees-management.php';

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



            Route::prefix('checklist-items')
                ->name('checklist-items.')
                ->group(function () {
                    Route::get('/pending-delivers', PendingItems::class)->name('pending-delivers');
                    Route::get('/pending-returns', PendingReturns::class)->name('pending-returns');
                    Route::get('/done-requests', DoneRequests::class)->name('done-requests');
                    Route::get('/all', AllItems::class)->name('index');
                });




            Route::get('/notifications', \App\Livewire\Notifications\Index::class)->name('notifications.index');

            Route::get('/news', \App\Livewire\News\NewsModule::class)->name('news.index')->middleware('auth');

            Route::get('/employee-statistics/{mode}', [EmployeeStatistics::class, 'getStatistics']);

            Route::get('/badges', \App\Livewire\Badges\BadgesModule::class)->name('badges.index');

            Route::get('/news/create', \App\Livewire\News\NewsCreate::class)->name('news.create');
            Route::get('/news/{news}/edit', \App\Livewire\News\NewsCreate::class)->name('news.edit');

            Route::get('/course-categories', CourseCategoryModule::class)->name('course-categories.index');
        });

        require __DIR__ . '/auth.php';
    },
);
