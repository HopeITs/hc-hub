<?php

declare(strict_types=1);

namespace Modules\Hr\Console\Journeys;

use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\OffboardingJourney;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Modules\Hr\Support\Journeys\OffboardingJourneys;
use Throwable;

/**
 * DEC-09 increment 2: automatic deactivation of a volunteer whose last real
 * contract has ended. For every active, complete employee with real contracts
 * and none current, future or still due to auto-renew:
 *
 *  - the exit journey is started if nothing started it before;
 *  - before the grace period (config('journeys.exit_grace_days')) has passed,
 *    city HR gets ONE in-app heads-up, claimed against the contract and its
 *    end date;
 *  - once it has passed, the employee is deactivated (card off) and the
 *    reason is logged under "offboarding".
 *
 * Open exit journeys are then checked for completion.
 */
class DeactivateDepartedEmployees extends Command
{
    public const LEDGER_KIND = 'last-contract-ended';

    protected $signature = 'hr:deactivate-departed {--dry-run : List who would be deactivated or warned, change nothing}';

    protected $description = 'Deactivate volunteers whose last contract ended more than the grace period ago, and warn city HR once when it ends';

    public function handle(OffboardingJourneys $journeys, DeliveryLedger $ledger): int
    {
        $today = $ledger->today();
        $dryRun = (bool) $this->option('dry-run');
        $deactivated = 0;
        $warned = 0;

        $this->departedCandidates($today)
            ->chunkById(100, function (Collection $employees) use ($journeys, $ledger, $today, $dryRun, &$deactivated, &$warned): void {
                foreach ($employees as $employee) {
                    $departure = $journeys->departure($employee, $today);

                    if ($departure === null) {
                        continue;
                    }

                    $due = $departure['deactivate_on']->toDateString() <= $today->toDateString();

                    if ($dryRun) {
                        $this->line(sprintf(
                            '%s #%d %s: last contract #%d ended %s, deactivation %s',
                            $due ? 'DEACTIVATE' : 'WARN',
                            $employee->id,
                            $employee->nameForLocale('en'),
                            $departure['contract']->id,
                            $departure['ended_on']->toDateString(),
                            $departure['deactivate_on']->toDateString(),
                        ));

                        continue;
                    }

                    $journeys->startForDeparture($departure['contract'], OffboardingJourney::TRIGGER_CONTRACT_ENDED);

                    if ($due) {
                        if ($journeys->deactivateDeparted($employee, $today)) {
                            $deactivated++;
                            Log::info('hr:deactivate-departed deactivated an employee', [
                                'employee_id' => $employee->id,
                                'reason' => 'last_contract_ended',
                                'contract_id' => $departure['contract']->id,
                                'ended_on' => $departure['ended_on']->toDateString(),
                            ]);
                        }

                        continue;
                    }

                    $warned += $this->headsUp($ledger, $employee, $departure);
                }
            });

        if (! $dryRun) {
            OffboardingJourney::query()
                ->whereNull('completed_at')
                ->chunkById(100, fn (Collection $open) => $open->each(fn (OffboardingJourney $journey) => $journeys->settle($journey)));
        }

        $this->info("Deactivated {$deactivated} employee(s); sent {$warned} heads-up notice(s).");

        return self::SUCCESS;
    }

    /**
     * Active, complete, with real contracts and none current or future. The
     * pending-auto-renewal case is left to OffboardingJourneys::departure.
     *
     * @return Builder<Employee>
     */
    private function departedCandidates(CarbonInterface $today): Builder
    {
        $day = $today->toDateString();

        return Employee::query()
            ->completeRecords()
            ->where('employees.is_active', true)
            ->whereHas('realContracts')
            ->whereDoesntHave('realContracts', fn (Builder $q) => $q
                ->whereNull('terminated_at')
                ->whereNull('termination_reason_id')
                ->where(fn (Builder $w) => $w
                    ->whereDate('start_date', '>', $day)
                    ->orWhere(fn (Builder $running) => $running
                        ->where('is_active', true)
                        ->where(fn (Builder $e) => $e->whereNull('end_date')->orWhereDate('end_date', '>=', $day)))));
    }

    /**
     * @param  array{contract: Contract, ended_on: CarbonInterface, deactivate_on: CarbonInterface}  $departure
     */
    private function headsUp(DeliveryLedger $ledger, Employee $employee, array $departure): int
    {
        $sent = 0;
        $contractId = $departure['contract']->id;
        // Keyed to the end date, not today: one notice per departure, ever.
        $day = $departure['ended_on'];
        $notice = new JourneyNotice('last-contract-ended', [
            'name' => ['en' => $employee->nameForLocale('en'), 'ar' => $employee->nameForLocale('ar')],
            'ended' => $departure['ended_on']->format('d-m-Y'),
            'date' => $departure['deactivate_on']->format('d-m-Y'),
        ], route('hr.employees.show', ['employee' => $employee->username, 'tab' => 'journeys']));

        foreach ($ledger->cityHr($employee->city_id) as $user) {
            if ($ledger->claim(self::LEDGER_KIND, $user->id, [$contractId], $day) === []) {
                continue;
            }

            try {
                $user->notify($notice);
                $ledger->markDelivered(self::LEDGER_KIND, $user->id, [$contractId], $day);
                $sent++;
            } catch (Throwable $e) {
                $ledger->release(self::LEDGER_KIND, $user->id, [$contractId], $day);
                report($e);
            }
        }

        return $sent;
    }
}
