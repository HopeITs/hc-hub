<?php

use App\Console\Commands\PruneTaskAttempts;
use App\Models\InAppFeedback;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Modules\Hr\Console\Commands\DeactivateContract;
use Modules\Hr\Console\Commands\DeactivateEmployee;
use Modules\Hr\Console\Commands\ProcessContractAutoRenewals;
use Modules\Hr\Console\Commands\SendContractExpirationWarnings;
use Modules\Hr\Console\Commands\SendLeaveDecisionReminders;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(DeactivateContract::class)->dailyAt('21:30');
Schedule::command(SendContractExpirationWarnings::class)->dailyAt('08:00');
// DEC-05 revision 2 (G-C-1): one reminder per pending leave request.
Schedule::command(SendLeaveDecisionReminders::class)->dailyAt('08:05');
Schedule::command(ProcessContractAutoRenewals::class)->dailyAt('21:35');
Schedule::command(DeactivateEmployee::class)->weekly();
// X2-I / DEC-10: task-attempt telemetry is deleted after 90 days.
Schedule::command(PruneTaskAttempts::class)->dailyAt('03:10');
// D3 / DEC-08: in-app feedback is deleted after one year (InAppFeedback::prunable()).
Schedule::command('model:prune', ['--model' => [InAppFeedback::class]])->dailyAt('03:20');
Schedule::call(function () {
    Log::info('الكرون جوب يعمل بنجاح!');
})->daily();
