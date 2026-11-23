<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\Attendance\IclockController;

/*
| Where a ZKTeco device's "Cloud Server" (ADMS) calls. Outside the language prefix, the session and
| the login: a device has none of those. It is believed by its registered serial number only
| (Modules\Hr\Support\Attendance\DevicePush). Loaded by HrServiceProvider with the "api" group and the
| "iclock" rate limit, not with the auth group the other area files share.
*/
Route::match(['get', 'post'], 'iclock/cdata', [IclockController::class, 'cdata'])->name('hr.iclock.cdata');
Route::match(['get', 'post'], 'iclock/getrequest', [IclockController::class, 'getrequest'])->name('hr.iclock.getrequest');
Route::match(['get', 'post'], 'iclock/devicecmd', [IclockController::class, 'devicecmd'])->name('hr.iclock.devicecmd');
