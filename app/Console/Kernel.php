<?php

namespace App\Console;

use App\Console\Commands\DeactivateContract;
use App\Console\Commands\DeactivateEmployee;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [

        DeactivateContract::class,
        DeactivateEmployee::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('deactivate:contract')->dailyAt('09:00');
        $schedule->command('deactivate:employee')->weekly();
        $schedule->call(function () {
    \Log::info('الكرون جوب يعمل بنجاح!');
})->everyMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
