<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\Contracts\ContractAmendmentPrintController;
use Modules\Hr\Http\Controllers\Contracts\ContractLegalPrintController;

/*
| Contract amendments (ملحق عقد) and the contract's full legal text: a
| contract's amendments, draft / edit one, its printable sheet, and the
| printable signed contract. Loaded by HrServiceProvider inside web + auth +
| 2FA. Pages authorize against ContractPolicy / ContractAmendmentPolicy.
*/
Route::prefix('hr')->name('hr.')->group(function (): void {
    Route::livewire('contracts/{contract}/amendments', 'hr::contract-amendments.index')
        ->whereNumber('contract')
        ->can('contracts.view')
        ->name('contracts.amendments.index');

    Route::livewire('contracts/{contract}/amend', 'hr::contract-amendments.form')
        ->whereNumber('contract')
        ->can('contracts.amend')
        ->name('contracts.amend');

    Route::livewire('contracts/{contract}/amendments/{amendment}/edit', 'hr::contract-amendments.form')
        ->whereNumber(['contract', 'amendment'])
        ->scopeBindings()
        ->can('contracts.amend')
        ->name('contracts.amendments.edit');

    Route::get('contracts/{contract}/amendments/{amendment}/print', ContractAmendmentPrintController::class)
        ->whereNumber(['contract', 'amendment'])
        ->scopeBindings()
        ->can('exportAmendment', 'amendment')
        ->middleware('throttle:30,1')
        ->name('contracts.amendments.print');

    Route::get('contracts/{contract}/legal', ContractLegalPrintController::class)
        ->whereNumber('contract')
        ->can('export', 'contract')
        ->middleware('throttle:30,1')
        ->name('contracts.legal');
});
