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

        $this->components->info('Renewed '.$contracts->count().' contract(s).');

        return self::SUCCESS;
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
