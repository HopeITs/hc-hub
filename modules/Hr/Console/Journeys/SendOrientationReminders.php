<?php

declare(strict_types=1);

namespace Modules\Hr\Console\Journeys;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\Hr\Models\OnboardingJourney;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Throwable;

/**
 * D4-I: a new volunteer whose orientation session is past its due date gets
 * ONE in-app reminder to HR in their city (HR-SYR when that city has no HR).
 * The ledger claim is keyed to the journey and its start day, so a later run,
 * a retry or a second worker never reminds again. Inactive staff are skipped.
 */
class SendOrientationReminders extends Command
{
    public const LEDGER_KIND = 'orientation-overdue';

    protected $signature = 'hr:send-orientation-reminders';

    protected $description = 'Remind city HR once about new volunteers whose orientation session is overdue';

    public function handle(DeliveryLedger $ledger): int
    {
        $today = $ledger->today();
        $sent = 0;

        OnboardingJourney::query()
            ->select(['id', 'employee_id', 'started_at', 'orientation_due_on'])
            ->whereNull('orientation_completed_at')
            ->whereDate('orientation_due_on', '<', $today->toDateString())
            ->whereHas('employee', fn (Builder $query) => $query->where('is_active', true))
            ->with('employee:id,city_id,username,first_name_en,last_name_en,first_name_ar,last_name_ar')
            ->chunkById(100, function ($journeys) use ($ledger, &$sent): void {
                foreach ($journeys as $journey) {
                    $employee = $journey->employee;
                    $day = $journey->started_at->timezone(config('app.timezone'))->startOfDay();
                    $notice = new JourneyNotice('orientation-overdue', [
                        'name' => ['en' => $employee->nameForLocale('en'), 'ar' => $employee->nameForLocale('ar')],
                        'date' => $journey->orientation_due_on?->format('d-m-Y') ?? '',
                    ], route('hr.employees.show', ['employee' => $employee->username, 'tab' => 'journeys']));

                    foreach ($ledger->cityHr($employee->city_id) as $user) {
                        if ($ledger->claim(self::LEDGER_KIND, $user->id, [$journey->id], $day) === []) {
                            continue;
                        }

                        try {
                            $user->notify($notice);
                            $ledger->markDelivered(self::LEDGER_KIND, $user->id, [$journey->id], $day);
                            $sent++;
                        } catch (Throwable $e) {
                            $ledger->release(self::LEDGER_KIND, $user->id, [$journey->id], $day);
                            report($e);
                        }
                    }
                }
            });

        $this->info("Sent {$sent} orientation reminder(s).");

        return self::SUCCESS;
    }
}
