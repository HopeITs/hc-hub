<?php

use App\Http\Controllers\ContractExportController;
use App\Livewire\Contract\ContractCreate;
use App\Livewire\Contract\ContractDetails;
use App\Livewire\Contract\ContractEdit;
use App\Livewire\Contract\ContractsModule;
use App\Livewire\Employee\EmployeeCreate;
use App\Livewire\Employee\EmployeeEdit;
use App\Livewire\Employee\EmployeeModule;
use App\Livewire\Employee\EmployeeProfile;
use App\Livewire\ProgramManagement\DefaultDeliverable\DefaultDeliverablesModule;

Route::prefix('employees')
    ->name('employees.')
    ->middleware('role:HR|super-admin|general-manager')
    ->group(function () {
        Route::get('/', EmployeeModule::class)->name('index');
        Route::get('/create', EmployeeCreate::class)->name('create');
        Route::get('/{employee}/edit', EmployeeEdit::class)->name('edit');

    });
Route::get('employees/{employee:username}/show', EmployeeProfile::class)->name('employees.show');
Route::get('/default-deliverables', DefaultDeliverablesModule::class)->middleware('role:HR|super-admin|general-manager')->name('default-deliverables.index');

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
