<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Models\Holiday;

/*
| Holidays, the birthday holiday and the team calendar. Loaded by
| HrServiceProvider inside the auth + 2FA group.
*/

// National HR and program managers (team-coverage-contract §6.1): HolidayPolicy::viewAny.
Route::livewire('hr/holidays', 'hr::leave.holidays')
    ->can('viewAny', Holiday::class)
    ->name('hr.leave.holidays');

// The volunteer's own birthday day and the requests waiting for their manager:
// everyone signed in, the page shows only what applies to them.
Route::livewire('hr/holidays/birthday-choices', 'hr::leave.birthday-choices')
    ->name('hr.leave.birthday-choices');

Route::livewire('hr/team-calendar', 'hr::leave.team-calendar')
    ->can('leaves.view-own')
    ->name('hr.leave.team-calendar');
