<?php

use Modules\Hr\Http\Controllers\ContractExportController;
use Modules\Hr\Livewire\Contract\ContractCreate;
use Modules\Hr\Livewire\Contract\ContractDetails;
use Modules\Hr\Livewire\Contract\ContractEdit;
use Modules\Hr\Livewire\Contract\ContractsModule;
use Modules\Hr\Livewire\Employee\EmployeeCreate;
use Modules\Hr\Livewire\Employee\EmployeeEdit;
use Modules\Hr\Livewire\Employee\EmployeeModule;
use Modules\Hr\Livewire\Employee\EmployeeProfile;
use Modules\Hr\Livewire\ProgramManagement\DefaultDeliverable\DefaultDeliverablesModule;

Route::prefix('employees')
    ->name('employees.')
    ->group(function () {
        Route::get('/', EmployeeModule::class)->middleware('active_permission:hr.employees.view')->name('index');
        Route::get('/create', EmployeeCreate::class)->middleware('active_permission:hr.employees.create')->name('create');
        Route::get('/{employee}/edit', EmployeeEdit::class)->middleware('active_permission:hr.employees.update')->name('edit');

    });
Route::get('employees/{employee:username}/show', EmployeeProfile::class)->name('employees.show');
Route::get('/default-deliverables', DefaultDeliverablesModule::class)->middleware('active_permission:hr.checklists.manage')->name('default-deliverables.index');

Route::prefix('contracts')
    ->name('contracts.')
    ->group(function () {
        Route::get('/', ContractsModule::class)->middleware('active_permission:hr.contracts.view')->name('index');
        Route::get('/create', ContractCreate::class)->middleware('active_permission:hr.contracts.create')->name('create');
        Route::get('/{contract}/edit', ContractEdit::class)->middleware('active_permission:hr.contracts.update')->name('edit');
        // The creation wizard again, prefilled from the contract being renewed.
        Route::get('/{contract}/renew', ContractCreate::class)->middleware('active_permission:hr.contracts.create')->name('renew');
        Route::get('/{contract}/export', [ContractExportController::class, 'pdf'])->middleware('active_permission:hr.contracts.export')->name('export');
        Route::get('/{contract}', ContractDetails::class)->middleware('active_permission:hr.contracts.view')->name('show');
    });

// The print layout as plain HTML, for iterating on it in devtools. It answers
// 404 outside the local environment (the controller checks, so the gate is
// testable) and otherwise holds the same export/salary/city gates as the PDF:
// the page carries the pay clause.
Route::get('contracts/{contract}/export-preview', [ContractExportController::class, 'preview'])
    ->middleware('active_permission:hr.contracts.export')
    ->name('contracts.export-preview');
