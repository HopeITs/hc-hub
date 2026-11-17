<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Attendance (HR timesheets from the device report)
|--------------------------------------------------------------------------
|
| Read by Modules\Hr\Support\Attendance. Overtime is not paid: approved
| hours are shown as free days (hours / the contract's daily hours).
*/
return [

    /*
     * A device number is the employee's ID unless HR linked it to someone by hand
     * (attendance_device_links). Turn off to match only linked numbers.
     */
    'device_number_is_employee_id' => true,

    /*
     * Weekend days when a center has none set: PHP day numbers, 0 = Sunday ... 6 = Saturday.
     */
    'default_weekends' => '0,6',

    /*
     * Daily hours when a contract has none.
     */
    'default_daily_hours' => 8,

    /*
     * The month: the employee may confirm it, the manager approves it (which approves the
     * overtime), HR locks it. On: the employee's confirmation is a step the manager waits for.
     */
    'require_employee_confirmation' => false,

    /*
     * A day counts as overtime only when the time at work (first to last punch) is at least
     * `threshold_minutes` beyond the contract's day; it is then counted in blocks of
     * `block_minutes`, rounded down. Work on a weekend or a holiday counts in full when
     * `weekend_counts_fully` is on.
     */
    'overtime' => [
        'threshold_minutes' => 60,
        'block_minutes' => 30,
        'weekend_counts_fully' => true,
    ],

];
