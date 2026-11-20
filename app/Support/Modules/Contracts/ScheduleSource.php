<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use Illuminate\Console\Scheduling\Schedule;

/**
 * The background jobs one module (or the platform itself) runs on a timer
 * (roadmap 4.13, D10). Registered with ModuleRegistry::addSchedule() from the
 * owner's ServiceProvider; AppServiceProvider applies every source to the
 * scheduler, so no job is scheduled anywhere else.
 *
 * tests/Feature/ScheduleTest.php pins the whole list: a job added, dropped or
 * retimed is a change to that test too.
 */
interface ScheduleSource
{
    public function schedule(Schedule $schedule): void;
}
