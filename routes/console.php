<?php

use App\Console\Commands\ApplyDueRecruitmentRequests;
use App\Console\Commands\DeactivateContract;
use App\Console\Commands\DeactivateEmployee;
use App\Console\Commands\ExpireDueHiringCampaigns;
use App\Console\Commands\ProcessContractAutoRenewals;
use App\Console\Commands\SendContractExpirationWarnings;
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
// Tidying, not a gate: every campaign check already measures the window
// against the clock, so a campaign that ends at noon stops accepting
// applications at noon rather than at the next sweep.
Schedule::command(ExpireDueHiringCampaigns::class)->dailyAt('00:35');
Schedule::call(function () {
    Log::info('الكرون جوب يعمل بنجاح!');
})->daily();
