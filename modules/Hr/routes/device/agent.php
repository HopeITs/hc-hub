<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\Attendance\AttendanceAgentController;

/*
| The attendance agent (modules/Hr/tools/attendance-agent) sends the punches it read from a device
| on its own network. Outside the language prefix, the session and the login: authenticated by the
| device's sync key (Authorization: Bearer zk_...), not by a person. Loaded by HrServiceProvider with
| the "api" group and the "attendance-agent" rate limit.
*/
Route::prefix('api/attendance/agent')->group(function (): void {
    Route::get('state', [AttendanceAgentController::class, 'state'])->name('hr.attendance.agent.state');
    Route::post('punches', [AttendanceAgentController::class, 'punches'])->name('hr.attendance.agent.punches');
});
