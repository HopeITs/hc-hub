<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\Attendance\AttendancePrintController;
use Modules\Hr\Http\Controllers\Attendance\AttendanceSignedController;
use Modules\Hr\Http\Controllers\Attendance\AttendanceUploadController;

/*
| Attendance from the device report: the month's timesheets (scoped by the
| active role, Modules\Hr\Support\Attendance\Roster), the printed Monthly
| TimeSheet, the signed copy of a month, and the upload of the report.
| Loaded by HrServiceProvider inside the auth + 2FA group.
*/
Route::prefix('hr/attendance')->name('hr.attendance.')->group(function (): void {
    Route::livewire('/', 'hr::attendance.timesheets')
        ->can('attendance.view-own')
        ->name('timesheets');

    Route::livewire('upload', 'hr::attendance.upload')
        ->can('attendance.manage')
        ->name('upload');

    // The devices that push their punches by themselves, and the centers they hang in.
    Route::livewire('devices', 'hr::attendance.devices')
        ->can('attendance.manage')
        ->name('devices');

    Route::post('upload', AttendanceUploadController::class)
        ->can('attendance.manage')
        ->middleware('throttle:20,1')
        ->name('upload.store');

    Route::get('print', AttendancePrintController::class)
        ->can('attendance.view-own')
        ->middleware('throttle:30,1')
        ->name('print');

    Route::get('signed/{month}', AttendanceSignedController::class)
        ->whereNumber('month')
        ->can('attendance.view-own')
        ->middleware('throttle:30,1')
        ->name('signed');
});
