<?php

use App\Http\Controllers\ContractExportController;
use App\Livewire\ActivityLog\ActivityLogModule;
use App\Livewire\Archives\ArchivedFilesModule;
use App\Livewire\Basics\{ChecklistItemModule,
    CityModule,
    ContractTypeModule,
    DepartmentModule,
    JobLevelModule,
    LeaveTypeModule};
use App\Livewire\ChecklistItems\{AllItems, DoneRequests, PendingItems, PendingReturns};
use App\Livewire\Contract\{ContractCreate, ContractDetails, ContractEdit, ContractsModule};
use App\Livewire\Statistics\ContractStatistics;
use App\Livewire\Statistics\GeneralStatistics;
use App\Livewire\Employee\{EmployeeCreate,
    EmployeeEdit,
    EmployeeModule,
    EmployeeProfile,
    Leave\AllLeaveRequestModule,
    Leave\CreateLeaveRequest,
    Leave\EmpLeaveBalanceModule,
    Leave\MyLeaveRequestModule,
    Leave\MyLeaveRequestTable};
use App\Livewire\Employee\EmployeeStatistics;
use App\Livewire\Position\{OrgChart, PositionCreate, PositionEdit, PositionsModule};
use App\Livewire\ProgramManagement\{Center\CenterDetails,
    Center\CentersModule,
    DefaultDeliverable\DefaultDeliverablesModule,
    Program\Create as ProgramCreate,
    Program\Detail as ProgramDetail,
    Program\Index as ProgramIndex,
    Program\Update as ProgramUpdate};
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
            Route::redirect('settings', 'settings/password');
            Route::get('settings/password', Password::class)->name('settings.password');

            Route::get('cities', CityModule::class)->name('cities.index');
            Route::get('contract-types', ContractTypeModule::class)->name('contract-types.index');
            Route::get('checklist-items-basics', ChecklistItemModule::class)->name('basic-checklist-items.index');
            Route::get('leave-types', LeaveTypeModule::class)->name('leave-types.index');
            Route::get('job-levels', JobLevelModule::class)->name('job-levels.index');
            Route::get('departments', DepartmentModule::class)->name('departments.index');
            Route::prefix('centers')
                ->name('centers.')
                ->group(function () {
                    Route::get('/', CentersModule::class)->name('index');
                    Route::get('/create', \App\Livewire\ProgramManagement\Center\CenterCreate::class)->name('create'); // ✅ Add this
                    Route::get('/{center}', CenterDetails::class)->name('show');
                });

            Route::prefix('positions')
                ->name('positions.')
                ->group(function () {
                    Route::get('/', PositionsModule::class)->name('index');
                    Route::get('/create', PositionCreate::class)->name('create');
                    Route::get('/{position}/edit', PositionEdit::class)->name('edit');
                });

            Route::get('/org-chart', OrgChart::class)->name('org.chart');

            Route::prefix('programs')
                ->name('programs.')
                ->group(function () {
                    Route::get('/', ProgramIndex::class)->name('index');
                    Route::get('/create', ProgramCreate::class)->name('create');
                    Route::get('/edit/{program:slug}', ProgramUpdate::class)->name('edit');
                    Route::get('/{program:slug}', ProgramDetail::class)->name('show');
                });


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
                    Route::get('/', AllItems::class)->name('index');
                });

            Route::get('/', \App\Livewire\Dashboard\Index::class)
                ->name('dashboard');

            Route::get('/general-statistics', GeneralStatistics::class)
                ->name('general-statistics');


            Route::get('/contract-statistics', ContractStatistics::class)
                ->name('contract-statistics');

            Route::get('/notifications', \App\Livewire\Notifications\Index::class)->name('notifications.index');

            Route::get('/news', \App\Livewire\News\NewsModule::class)->name('news.index')->middleware('auth');

            Route::get('/employee-statistics/{mode}', [EmployeeStatistics::class, 'getStatistics']);

            Route::get('/badges', \App\Livewire\Badges\BadgesModule::class)->name('badges.index');

            Route::get('/news/create', \App\Livewire\News\NewsCreate::class)->name('news.create');

            Route::get('/activity-log', ActivityLogModule::class)
                ->name('activity-log.index');


        });

        require __DIR__ . '/auth.php';
    },
);
