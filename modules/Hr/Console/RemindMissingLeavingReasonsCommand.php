<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Throwable;

/**
 * Tells HR of each city about people who left 30 to 365 days ago with no
 * termination reason on the contract, once a month: each HR user is claimed
 * for the month in notification_deliveries first, so a second run or a retry
 * sends nothing. Older gaps are left to the "Why people leave" report itself:
 * nagging about them for ever would only train people to ignore it.
 */
class RemindMissingLeavingReasonsCommand extends Command
{
    private const LEDGER_KIND = 'leaving-reasons-missing';

    protected $signature = 'leavers:remind-missing-reasons';

    protected $description = 'Remind HR to record the termination reason of people who left over a month ago';

    public function handle(DeliveryLedger $ledger): int
    {
        $contracts = Contract::query()
            ->latestPerEmployee()
            ->whereNull('contracts.termination_reason_id')
            ->whereNotNull('contracts.terminated_at')
            ->whereDate('contracts.terminated_at', '<=', now()->subDays(30)->toDateString())
            ->whereDate('contracts.terminated_at', '>=', now()->subDays(365)->toDateString())
            ->with('employee:id,city_id')
            ->get();

        /** @var array<int, array{user: User, count: int}> $perUser */
        $perUser = [];

        foreach ($contracts->groupBy(fn (Contract $contract): ?int => $contract->employee?->city_id) as $cityId => $group) {
            /** @var Collection<int, User> $hrUsers */
            $hrUsers = $ledger->cityHr($cityId === '' ? null : (int) $cityId);

            foreach ($hrUsers as $user) {
                $perUser[$user->id]['user'] = $user;
                $perUser[$user->id]['count'] = ($perUser[$user->id]['count'] ?? 0) + $group->count();
            }
        }

        $month = $ledger->today()->startOfMonth();
        $sent = 0;

        foreach ($perUser as $row) {
            if ($ledger->claim(self::LEDGER_KIND, $row['user']->id, [1], $month) === []) {
                continue;
            }

            try {
                $row['user']->notify(new JourneyNotice('leaving-reasons-missing', ['count' => $row['count']], route('hr.reports.leaving-reasons')));
                $ledger->markDelivered(self::LEDGER_KIND, $row['user']->id, [1], $month);
                $sent++;
            } catch (Throwable $e) {
                $ledger->release(self::LEDGER_KIND, $row['user']->id, [1], $month);
                report($e);
            }
        }

        $this->info("Reminded {$sent} HR user(s) about {$contracts->count()} leaver(s) with no reason.");

        return self::SUCCESS;
    }
}
