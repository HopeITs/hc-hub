<?php

use Illuminate\Support\Facades\Route;

/*
| HR statistics: the general figures, contract statistics per program or
| center, and the salary-period split. Loaded by HrServiceProvider inside
| web + auth + 2FA. Each page checks statistics.view itself (administrators
| pass).
*/
Route::prefix('hr/statistics')->name('hr.statistics.')->group(function (): void {
    Route::livewire('general', 'hr::statistics.general')->name('general');

    Route::livewire('contracts', 'hr::statistics.contracts')->name('contracts');

    Route::livewire('salaries', 'hr::statistics.salaries')->name('salaries');
});
