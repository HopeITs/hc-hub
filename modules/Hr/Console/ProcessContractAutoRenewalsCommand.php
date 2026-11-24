<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Hr\Actions\Contracts\ContractAutoRenewalService;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Throwable;

/**
 * Daily, after `contracts:deactivate-expired`: every ended contract with
 * auto-renew on gets its successor, and HR in each employee's city (HR-SYR
 * when that city has none) gets one inbox line naming who was renewed (the
 * old ContractsAutoRenewedSummary, in-app only as the other journey notices).
 */
class ProcessContractAutoRenewalsCommand extends Command
{
    protected $signature = 'contracts:process-auto-renewals';

    protected $description = 'Create successor contracts for ended contracts that renew automatically';

    public function handle(ContractAutoRenewalService $renewals, DeliveryLedger $ledger): int
    {
        $contracts = $renewals->process();

        $this->notifyCityHr($contracts, $ledger);

        $waiting = $this->notifyAwaitingNewYear($renewals->waitingForNewYear(), $ledger);

        $this->components->info('Renewed '.$contracts->count().' contract(s); told HR of '.$waiting.' newly waiting for the new year.');

        return self::SUCCESS;
    }

    /**
     * Tell city HR, once per contract, which contracts ended in October to
     * December and will only be renewed on 1 January. The ledger is keyed on
     * the contract's end date, so a re-run or a later night sends nothing new.
     *
     * @param  Collection<int, Contract>  $contracts
     */
    private function notifyAwaitingNewYear(Collection $contracts, DeliveryLedger $ledger): int
    {
        $told = [];

        $contracts
            ->filter(fn (Contract $contract): bool => $contract->employee?->city_id !== null)
            ->groupBy(fn (Contract $contract): int => (int) $contract->employee->city_id)
            ->each(function (Collection $group, int $cityId) use ($ledger, &$told): void {
                foreach ($ledger->cityHr($cityId) as $recipient) {
                    $new = $group->filter(fn (Contract $contract): bool => $ledger->claim(
                        'contract_renews_new_year', $recipient->id, [$contract->id], $contract->end_date,
                    ) !== [])->values();

                    if ($new->isEmpty()) {
                        continue;
                    }

                    $notice = new JourneyNotice('contracts-awaiting-new-year', [
                        'count' => $new->count(),
                        'date' => ContractAutoRenewalService::renewalStart($new->first())->toDateString(),
                        'names' => [
                            'en' => $new->map(fn (Contract $contract): string => $contract->employee->nameForLocale('en'))->implode(', '),
                            'ar' => $new->map(fn (Contract $contract): string => $contract->employee->nameForLocale('ar'))->implode('، '),
                        ],
                    ], route('hr.contracts.index'));

                    try {
                        $recipient->notify($notice);
                    } catch (Throwable $e) {
                        report($e);
                        $ledger->release('contract_renews_new_year', $recipient->id, $new->pluck('id')->all(), $new->first()->end_date);

                        continue;
                    }

                    foreach ($new as $contract) {
                        $ledger->markDelivered('contract_renews_new_year', $recipient->id, [$contract->id], $contract->end_date);
                        $told[$contract->id] = true;
                    }
                }
            });

        return count($told);
    }

    /**
     * @param  Collection<int, Contract>  $contracts
     */
    private function notifyCityHr(Collection $contracts, DeliveryLedger $ledger): void
    {
        if ($contracts->isEmpty()) {
            return;
        }

        EloquentCollection::make($contracts->all())->loadMissing('employee:id,city_id,first_name_en,last_name_en,first_name_ar,last_name_ar');

        $contracts
            ->filter(fn (Contract $contract): bool => $contract->employee?->city_id !== null)
            ->groupBy(fn (Contract $contract): int => (int) $contract->employee->city_id)
            ->each(function (Collection $group, int $cityId) use ($ledger): void {
                $notice = new JourneyNotice('contracts-auto-renewed', [
                    'count' => $group->count(),
                    'names' => [
                        'en' => $group->map(fn (Contract $contract): string => $contract->employee->nameForLocale('en'))->implode(', '),
                        'ar' => $group->map(fn (Contract $contract): string => $contract->employee->nameForLocale('ar'))->implode('، '),
                    ],
                ], route('hr.contracts.index'));

                foreach ($ledger->cityHr($cityId) as $user) {
                    try {
                        $user->notify($notice);
                    } catch (Throwable $e) {
                        report($e);
                    }
                }
            });
    }
}
