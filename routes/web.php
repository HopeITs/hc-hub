<?php

use App\Http\Controllers\ContractExportController;
use App\Livewire\Archives\ArchivedFilesModule;
use App\Livewire\Dashboard\Index as DashboardIndex;

use App\Livewire\ChecklistItems\{AllItems, DoneRequests, PendingItems, PendingReturns};
use App\Livewire\Contract\{ContractCreate, ContractDetails, ContractEdit, ContractsModule};
use App\Livewire\Statistics\ContractStatistics;
use App\Livewire\Statistics\GeneralStatistics;
use App\Livewire\Employee\{EmployeeCreate,
    EmployeeEdit,
    EmployeeModule,
    EmployeeProfile,
    Leave\AllLeaveRequestModule,
    Leave\EmpLeaveBalanceModule,
    Leave\MyLeaveRequestModule,
    Leave\MyLeaveRequestTable
};
use App\Livewire\Employee\EmployeeStatistics;
use App\Livewire\ProgramManagement\DefaultDeliverable\DefaultDeliverablesModule;
use App\Livewire\Settings\{Appearance, Password, Profile};
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;


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



            Route::redirect('settings', 'settings/profile');
            Route::get('settings/profile', Profile::class)->name('settings.profile');
            Route::get('settings/password', Password::class)->name('settings.password');
            Route::get('settings/appearance', Appearance::class)->name('settings.appearance');


            //Basic project routes
            require __DIR__ . '/basic-project.php';

            require __DIR__ . '/projects-management.php';

            Route::prefix('employees')
                ->name('employees.')
                ->group(function () {
                    Route::get('/', EmployeeModule::class)->name('index');
                    Route::get('/create', EmployeeCreate::class)->name('create');
                    Route::get('/{employee}/edit', EmployeeEdit::class)->name('edit');
                    Route::get('/{employee:username}/show', EmployeeProfile::class)->name('show');
                });
            Route::prefix('leaves')
                ->name('leaves.')
                ->group(function () {
                    Route::get('/', MyLeaveRequestModule::class)->name('index');
                    Route::get('/all', AllLeaveRequestModule::class)->name('all');
                    Route::get('/balance', EmpLeaveBalanceModule::class)->name('balance');
                });
            Route::get('/default-deliverables', DefaultDeliverablesModule::class)->name('default-deliverables.index');

            Route::prefix('contracts')
                ->name('contracts.')
                ->group(function () {
                    Route::get('/', ContractsModule::class)->name('index');
                    Route::get('/create', ContractCreate::class)->name('create');
                    Route::get('/{contract}/edit', ContractEdit::class)->name('edit');
                    Route::get('/{contract}/export', [ContractExportController::class, 'pdf'])->name('export');
                });

            Route::view('archived-files/{folder?}', 'archives.index')->name('archived-files.index');
            Route::get('my-leaves', MyLeaveRequestTable::class)->name('leaves.index');
            Route::get('my-folders/{folderId?}', ArchivedFilesModule::class)->defaults('isShared', false)->name('my-folders.index');
            Route::get('shared-folders/{folderId?}', ArchivedFilesModule::class)->defaults('isShared', true)->name('shared-folders.index');

            Route::get('/contracts/{contract}', ContractDetails::class)->name('contracts.show');

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

        });

        require __DIR__ . '/auth.php';
    },
);
