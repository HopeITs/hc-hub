<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Models\LeaveRequest;

/*
| Leave requests and balances. Loaded by HrServiceProvider inside the
| auth + 2FA group. The pages check the request itself (LeaveRequestPolicy).
*/

// The signed-in employee's balances and requests; ?new=1 opens the request form.
Route::livewire('hr/leaves', 'hr::leave.index')
    ->can('leaves.view-own')
    ->name('hr.leave.index');

// Requests of the people the user manages, or of HR's governorates.
Route::livewire('hr/leaves/team', 'hr::leave.team')
    ->can('viewAny', LeaveRequest::class)
    ->name('hr.leave.team');

// Employees' annual balances and their adjustment (HR).
Route::livewire('hr/leaves/balances', 'hr::leave.balances')
    ->can('leaves.manage-balances')
    ->name('hr.leave.balances');

Route::livewire('hr/leaves/{leaveRequest}', 'hr::leave.show')
    ->whereNumber('leaveRequest')
    ->name('hr.leave.show');
