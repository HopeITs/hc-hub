<?php

use App\Http\Controllers\ContractExportController;
use App\Livewire\Archives\ArchivedFilesModule;
use App\Livewire\Basics\{ChecklistItemModule,
    CityModule,
    ContractTypeModule,
    DepartmentModule,
    JobLevelModule,
    LeaveTypeModule
};
use App\Livewire\Contract\{ContractCreate, ContractDetails, ContractEdit, ContractsModule};
use App\Livewire\Employee\{EmployeeCreate,
    EmployeeEdit,
    EmployeeModule,
    EmployeeProfile,
    Leave\LeaveRequestModule,
    Leave\LeaveRequestTable};
use App\Livewire\Position\{OrgChart, PositionCreate, PositionEdit, PositionsModule};
use App\Livewire\ProgramManagement\{Center\CentersModule,
    DefaultDeliverable\DefaultDeliverablesModule,
    Program\Create as ProgramCreate,
    Program\Detail as ProgramDetail,
    Program\Index as ProgramIndex,
    Program\Update as ProgramUpdate
};
use App\Livewire\Settings\{Appearance, Password, Profile};
use App\Livewire\ChecklistItems\{PendingItems, AllItems, PendingReturns, DoneRequests};
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

        Route::get('/', fn() => view('welcome'))->name('home');

        Route::view('dashboard', 'dashboard')
            ->middleware(['auth', 'verified'])
            ->name('dashboard');

        Route::middleware(['auth'])->group(function () {
            Route::redirect('settings', 'settings/profile');
            Route::get('settings/profile', Profile::class)->name('settings.profile');
            Route::get('settings/password', Password::class)->name('settings.password');
            Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

            Route::get('cities', CityModule::class)->name('cities.index');
            Route::get('contract-types', ContractTypeModule::class)->name('contract-types.index');
            Route::get('checklist-items-basics', ChecklistItemModule::class)->name('basic-checklist-items.index');
            Route::get('leave-types', LeaveTypeModule::class)->name('leave-types.index');
            Route::get('job-levels', JobLevelModule::class)->name('job-levels.index');
            Route::get('departments', DepartmentModule::class)->name('departments.index');

            Route::get('/centers', CentersModule::class)->name('centers.index');

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
                    Route::get('my-leaves', LeaveRequestModule::class)->name('index');

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
            Route::get('my-leaves', LeaveRequestTable::class)->name('leaves.index');
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
        });

        require __DIR__ . '/auth.php';
    },
);
