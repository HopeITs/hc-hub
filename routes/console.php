<?php

use App\Console\Commands\DeactivateContract;
use App\Console\Commands\DeactivateEmployee;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(DeactivateContract::class)->dailyAt('21:30');
Schedule::command(DeactivateEmployee::class)->weekly();
Schedule::call(function () {
    Log::info('الكرون جوب يعمل بنجاح!');
})->daily();
