<?php


use App\Livewire\ProgramManagement\DefaultDeliverable\DefaultDeliverablesModule;
use App\Livewire\Contract\{ContractCreate, ContractDetails, ContractEdit, ContractsModule};
use App\Livewire\Employee\{EmployeeCreate,
    EmployeeEdit,
    EmployeeModule,
    EmployeeProfile};
use App\Http\Controllers\ContractExportController;

Route::prefix('employees')
    ->name('employees.')
    ->middleware('role:HR|super-admin|general-manager')
    ->group(function () {
        Route::get('/', EmployeeModule::class)->name('index');
        Route::get('/create', EmployeeCreate::class)->name('create');
        Route::get('/{employee}/edit', EmployeeEdit::class)->name('edit');
        Route::get('/{employee:username}/show', EmployeeProfile::class)->name('show');
    });

Route::get('/default-deliverables', DefaultDeliverablesModule::class)    ->middleware('role:HR|super-admin|general-manager')->name('default-deliverables.index');

Route::prefix('contracts')
    ->name('contracts.')
    ->middleware('role:HR|super-admin|general-manager')
    ->group(function () {
        Route::get('/', ContractsModule::class)->name('index');
        Route::get('/create', ContractCreate::class)->name('create');
        Route::get('/{contract}/edit', ContractEdit::class)->name('edit');
        Route::get('/{contract}/export', [ContractExportController::class, 'pdf'])->name('export');
        Route::get('/{contract}', ContractDetails::class)->name('show');
    });













