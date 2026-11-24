<?php

use Illuminate\Support\Facades\Route;

/*
| The HR reports hub and its reports. Loaded by HrServiceProvider inside web +
| auth + 2FA. Every page re-checks through ReportCatalog::allows() (HR and the
| head-office roles, plus the permission the report's data already needs).
*/
Route::prefix('hr/reports')->name('hr.reports.')->group(function (): void {
    Route::livewire('/', 'hr::reports.index')->name('index');

    Route::livewire('leaving-reasons', 'hr::reports.leaving-reasons')->name('leaving-reasons');

    Route::livewire('recruitment-funnel', 'hr::reports.recruitment-funnel')->name('recruitment-funnel');

    Route::livewire('leave', 'hr::reports.leave')->name('leave');

    Route::livewire('vacancies', 'hr::reports.vacancies')->name('vacancies');

    Route::livewire('training', 'hr::reports.training')->name('training');

    Route::livewire('profile-completeness', 'hr::reports.profile-completeness')->name('profile-completeness');
});
