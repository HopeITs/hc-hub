<?php

use App\Console\Commands\ApplyDueRecruitmentRequests;
use App\Console\Commands\DeactivateContract;
use App\Console\Commands\DeactivateEmployee;
use App\Console\Commands\ProcessContractAutoRenewals;
use App\Console\Commands\ScanRecruitmentFiles;
use App\Console\Commands\SendContractExpirationWarnings;
use App\Console\Commands\SyncRecruitmentCampaignStates;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(DeactivateContract::class)->dailyAt('21:30');
Schedule::command(SendContractExpirationWarnings::class)->dailyAt('08:00');
Schedule::command(ProcessContractAutoRenewals::class)->dailyAt('21:35');
Schedule::command(DeactivateEmployee::class)->weekly();
// Early, so a reduction effective today is carried out before the contract
// sweeps that read `exist_emp` run in the evening.
Schedule::command(ApplyDueRecruitmentRequests::class)->dailyAt('00:30');
// Keep scheduled starts and ends reflected in the stored campaign status.
// Public access still rechecks the clock on every request.
Schedule::command(SyncRecruitmentCampaignStates::class)->everyMinute()->withoutOverlapping();
// Safety net for the antivirus: anything still in quarantine (a queue that
// was down, clamd unreachable through every retry) is queued again.
Schedule::command(ScanRecruitmentFiles::class)->everyFifteenMinutes()->withoutOverlapping();
Schedule::call(function () {
    Log::info('الكرون جوب يعمل بنجاح!');
})->daily();
