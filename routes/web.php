<?php

use App\Http\Controllers\ContractExportController;
use App\Livewire\Archives\ArchivedFilesModule;
use App\Livewire\Basics\{
    ChecklistItemModule,
    CityModule,
    ContractTypeModule,
    DepartmentModule,
    JobLevelModule,
    LeaveTypeModule
};
use App\Livewire\Contract\{
    ContractCreate,
    ContractDetails,
    ContractEdit,
    ContractsModule
};
use App\Livewire\Employee\{
    EmployeeCreate,
    EmployeeEdit,
    EmployeeModule
};
use App\Livewire\Position\{
    PositionCreate,
    PositionEdit,
    PositionsModule
};
use App\Livewire\ProgramManagement\{
    CentersModule,
    DefaultDeliverablesModule,
    Program\Create as ProgramCreate,
    Program\Detail as ProgramDetail,
    Program\Index as ProgramIndex,
    Program\Update as ProgramUpdate
};
use App\Livewire\Settings\{
    Appearance,
    Password,
    Profile
};
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Livewire\Livewire;

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
], function () {

    Livewire::setUpdateRoute(fn ($handle) => Route::post('/livewire/update', $handle));

    Route::get('/', fn () => view('welcome'))->name('home');

    Route::view('dashboard', 'dashboard')
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

    Route::middleware(['auth'])->group(function () {

        Route::redirect('settings', 'settings/profile');
        Route::get('settings/profile',    Profile::class)->name('settings.profile');
        Route::get('settings/password',   Password::class)->name('settings.password');
        Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

        Route::get('cities',          CityModule::class)->name('cities.index');
        Route::get('contract-types',  ContractTypeModule::class)->name('contract-types.index');
        Route::get('checklist-items', ChecklistItemModule::class)->name('checklist-items.index');
        Route::get('leave-types',     LeaveTypeModule::class)->name('leave-types.index');
        Route::get('job-levels',      JobLevelModule::class)->name('job-levels.index');
        Route::get('departments',     DepartmentModule::class)->name('departments.index');

        Route::get('/centers', CentersModule::class)->name('centers.index');

        Route::prefix('positions')->name('positions.')->group(function () {
            Route::get('/',                PositionsModule::class)->name('index');
            Route::get('/create',          PositionCreate::class)->name('create');
            Route::get('/{position}/edit', PositionEdit::class)->name('edit');
        });

        Route::prefix('programs')->name('programs.')->group(function () {
            Route::get('/',                    ProgramIndex::class)->name('index');
            Route::get('/create',              ProgramCreate::class)->name('create');
            Route::get('/edit/{program:slug}', ProgramUpdate::class)->name('edit');
            Route::get('/{program:slug}',      ProgramDetail::class)->name('show');
        });

        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/',                EmployeeModule::class)->name('index');
            Route::get('/create',          EmployeeCreate::class)->name('create');
            Route::get('/{employee}/edit', EmployeeEdit::class)->name('edit');
        });

        Route::get('/default-deliverables', DefaultDeliverablesModule::class)
            ->name('default-deliverables.index');

        Route::prefix('contracts')->name('contracts.')->group(function () {
            Route::get('/',                ContractsModule::class)->name('index');
            Route::get('/create',          ContractCreate::class)->name('create');
            Route::get('/{contract}/edit', ContractEdit::class)->name('edit');
            Route::get('/{contract}/export', [ContractExportController::class, 'pdf'])
                ->name('export');
        });

        Route::view('archived-files/{folder?}', 'archives.index')
            ->name('archived-files.index');

        Route::get('my-folders/{folderId?}', ArchivedFilesModule::class)
            ->defaults('isShared', false)
            ->name('my-folders.index');

        Route::get('shared-folders/{folderId?}', ArchivedFilesModule::class)
            ->defaults('isShared', true)
            ->name('shared-folders.index');

        Route::get('/contracts/{contract}', ContractDetails::class)->name('contracts.show');
    });

    require __DIR__ . '/auth.php';
});
