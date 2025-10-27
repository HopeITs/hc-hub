<?php

namespace App\Console;

use App\Console\Commands\NotifyExpiringContracts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        NotifyExpiringContracts::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('notify:expiry-contracts')->dailyAt('10:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
