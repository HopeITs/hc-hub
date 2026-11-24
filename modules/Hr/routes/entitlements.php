<?php

use Illuminate\Support\Facades\Route;

/*
| The Permissions Library (track 5): professional access tracked outside the
| system. HR roles and the administrators; the page re-checks the active role
| and the city on mount and on every action.
| Loaded by HrServiceProvider inside web + auth + 2FA.
*/
Route::prefix('hr')->name('hr.')->group(function (): void {
    Route::livewire('entitlements', 'hr::entitlements.index')
        ->can('entitlements.manage-library')
        ->name('entitlements.index');
});
