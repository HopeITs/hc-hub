<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\Contracts\ContractPrintController;

/*
| Contracts: the register, a contract's page, create / edit / renew, and the
| print page. Loaded by HrServiceProvider inside web + auth + 2FA. Each page
| authorizes against ContractPolicy itself.
*/
Route::prefix('hr')->name('hr.')->group(function (): void {
    Route::livewire('contracts', 'hr::contracts.index')
        ->can('contracts.view')
        ->name('contracts.index');

    Route::livewire('contracts/create', 'hr::contracts.form')
        ->can('contracts.create')
        ->name('contracts.create');

    Route::livewire('contracts/{contract}/edit', 'hr::contracts.form')
        ->whereNumber('contract')
        ->can('contracts.update')
        ->name('contracts.edit');

    Route::livewire('contracts/{contract}/renew', 'hr::contracts.form')
        ->whereNumber('contract')
        ->can('contracts.create')
        ->name('contracts.renew');

    Route::get('contracts/{contract}/print', ContractPrintController::class)
        ->whereNumber('contract')
        ->can('export', 'contract')
        ->middleware('throttle:30,1')
        ->name('contracts.print');

    Route::livewire('contracts/{contract}', 'hr::contracts.show')
        ->whereNumber('contract')
        ->can('contracts.view')
        ->name('contracts.show');
});
