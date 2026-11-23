<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Leave\BirthdayHoliday;
use Modules\Hr\Support\Leave\BirthdayHolidayNotifier;

/**
 * A volunteer whose birthday falls on a weekend or a holiday in the next
 * config('birthday-holiday.notify_days_ahead') days, and who has not asked for
 * another day yet, is told to pick a working day in the same week. Once per
 * (employee, year, holiday): the ledger claim is keyed to the holiday and 1
 * January of the birthday's year. Service agreements get no birthday holiday,
 * so BirthdayHoliday gives them no occurrence at all.
 */
class NotifyBirthdayConflictsCommand extends Command
{
    public const LEDGER_KIND = 'birthday-choice-needed';

    protected $signature = 'birthday:notify-conflicts';

    protected $description = 'Ask volunteers whose birthday falls on a weekend or holiday to pick another day in the same week';

    public function handle(DeliveryLedger $ledger, BirthdayHoliday $birthdays, BirthdayHolidayNotifier $notifier): int
    {
        $from = CarbonImmutable::instance($ledger->today())->startOfDay();
        $to = $from->addDays(max(0, (int) config('birthday-holiday.notify_days_ahead', 14)));
        $sent = 0;

        if (! Holiday::query()->where('kind', Holiday::KIND_BIRTHDAY)->occurringBetween($from, $to)->exists()) {
            $this->components->info('Sent 0 birthday reminder(s).');

            return self::SUCCESS;
        }

        $monthDays = $this->monthDays($from, $to);

        Employee::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNotNull('actual_dob')->orWhereNotNull('dob'))
            ->select(['id', 'city_id', 'dob', 'actual_dob', 'is_active'])
            ->lazyById()
            ->filter(fn (Employee $employee): bool => isset($monthDays[CarbonImmutable::parse($employee->actual_dob ?? $employee->dob)->format('m-d')]))
            ->each(function (Employee $employee) use ($birthdays, $notifier, $from, $to, &$sent): void {
                $contract = $employee->latestActiveContract();

                if ($contract === null) {
                    return;
                }

                foreach ($birthdays->occurrencesFor($contract, $from, $to) as $occurrence) {
                    if (! $occurrence['conflict'] || $occurrence['choice'] !== null) {
                        continue;
                    }

                    $claimDay = CarbonImmutable::create($occurrence['year'], 1, 1);

                    foreach ($notifier->accountsOf($employee->id) as $user) {
                        if ($notifier->choiceNeeded($user, (int) $occurrence['holiday']->id, $occurrence['date'], $claimDay, self::LEDGER_KIND)) {
                            $sent++;
                        }
                    }
                }
            });

        $this->components->info("Sent {$sent} birthday reminder(s).");

        return self::SUCCESS;
    }

    /**
     * The month-days whose birthday lands in [$from, $to]; 29 February is
     * kept on 28 February in other years, as BirthdayHoliday does.
     *
     * @return array<string, true>
     */
    private function monthDays(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = [];

        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $days[$day->format('m-d')] = true;

            if ($day->format('m-d') === '02-28' && ! $day->isLeapYear()) {
                $days['02-29'] = true;
            }
        }

        return $days;
    }
}
