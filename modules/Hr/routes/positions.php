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

    // Critical positions and who could fill them: the policy (HR of the city, the general manager,
    // the administrators) is the gate on the page as well as the permission at the door.
    Route::livewire('positions/critical', 'hr::positions.critical')
        ->can('positions.view-replacements')
        ->name('positions.critical');

    Route::livewire('positions/{position}/replacements', 'hr::positions.replacements')
        ->whereNumber('position')
        ->can('positions.view-replacements')
        ->name('positions.replacements');

    Route::livewire('team', 'hr::positions.team')
        ->can('positions.view')
        ->name('positions.team');
});
