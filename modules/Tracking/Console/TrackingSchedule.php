<?php

declare(strict_types=1);

namespace Modules\Tracking\Console;

use App\Support\Modules\Contracts\ScheduleSource;
use Illuminate\Console\Scheduling\Schedule;

/**
 * Tracking's background jobs: the monthly reports are prepared before the
 * working day starts, plan and proposal reminders go out at seven, and the
 * monthly digest at eight.
 */
final class TrackingSchedule implements ScheduleSource
{
    public function schedule(Schedule $schedule): void
    {
        $schedule->command('monthly-reports:prepare')->dailyAt('05:00')->withoutOverlapping()->onOneServer();
        $schedule->command('annual-plans:remind')->dailyAt('07:00')->withoutOverlapping()->onOneServer();
        $schedule->command('proposals:remind-owners')->dailyAt('07:05')->withoutOverlapping()->onOneServer();
        $schedule->command('reports:monthly-digest')->dailyAt('08:00')->withoutOverlapping()->onOneServer();
    }
}
