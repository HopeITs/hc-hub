<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\ScheduleSource;
use Illuminate\Console\Scheduling\Schedule;

final class SampleSchedule implements ScheduleSource
{
    public const string Name = 'sample:tick';

    public function schedule(Schedule $schedule): void
    {
        $schedule->call(fn (): null => null)->name(self::Name)->daily();
    }
}
