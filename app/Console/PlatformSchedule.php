<?php

declare(strict_types=1);

namespace App\Console;

use App\Models\Activity;
use App\Models\TrustedDevice;
use App\Models\UserNotification;
use App\Support\Modules\Contracts\ScheduleSource;
use Illuminate\Console\Scheduling\Schedule;

/**
 * The jobs the platform itself owns: the notification inbox (snoozes, alert
 * scans, digests) and housekeeping (old rows, unreferenced files, expired
 * cache). Each module schedules its own jobs from its own source.
 */
final class PlatformSchedule implements ScheduleSource
{
    public function schedule(Schedule $schedule): void
    {
        $schedule->command('notifications:wake-snoozed')->everyMinute()->withoutOverlapping();
        $schedule->command('notifications:scan frequent')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('notifications:scan daily')->dailyAt('06:00')->withoutOverlapping();
        $schedule->command('notifications:digests')->everyMinute()->withoutOverlapping();

        $schedule->command('model:prune', ['--model' => [Activity::class, TrustedDevice::class, UserNotification::class]])->daily();
        $schedule->command('files:prune')->dailyAt('03:00')->withoutOverlapping();
        $schedule->command('cache:prune-expired')->hourly()->withoutOverlapping();
    }
}
