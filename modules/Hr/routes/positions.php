<?php

use Illuminate\Support\Facades\Route;

/*
| Positions: the register (create / edit in a modal) and the team chart.
| Loaded by HrServiceProvider inside web + auth + 2FA.
*/
Route::prefix('hr')->name('hr.')->group(function (): void {
    Route::livewire('positions', 'hr::positions.index')
        ->can('positions.view')
        ->name('positions.index');

    Route::livewire('team', 'hr::positions.team')
        ->can('positions.view')
        ->name('positions.team');
});
