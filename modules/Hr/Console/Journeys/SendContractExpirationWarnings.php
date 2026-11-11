<?php

declare(strict_types=1);

namespace Modules\Hr\Console\Journeys;

use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Modules\Hr\Support\Journeys\RenewalJourneys;
use Throwable;

/**
 * The renewal journey's first step (R1): the direct manager of a volunteer
 * whose auto-renewing annual or service contract ends in exactly 30, 21, 14
 * or 7 days gets an in-app reminder. Each contract/manager/day is claimed in
 * notification_deliveries first, which is also what the journey reads as
 * "warning sent".
 */
class SendContractExpirationWarnings extends Command
{
    /** Read by the renewal journey as the record of a warning sent. */
    public const LEDGER_KIND = 'contract-expiry-warning';

    /** @var list<int> */
    private const REMINDER_DAYS = [30, 21, 14, 7];

    protected $signature = 'hr:send-contract-expiration-warnings';

    protected $description = 'Send direct managers scheduled warnings for auto-renewable contracts nearing expiration';

    public function handle(DeliveryLedger $ledger, RenewalJourneys $journeys): int
    {
        $day = $ledger->today();
        $sent = 0;

        foreach ($this->expiringContracts($day) as $contract) {
            $manager = $journeys->managerAccount($contract);

            if ($manager === null || $ledger->claim(self::LEDGER_KIND, $manager->id, [$contract->id], $day) === []) {
                continue;
            }

            $employee = $contract->employee;

            try {
                $manager->notify(new JourneyNotice('contract-expiring', [
                    'name' => ['en' => $employee?->nameForLocale('en') ?? '', 'ar' => $employee?->nameForLocale('ar') ?? ''],
                    'date' => $contract->end_date?->format('d-m-Y') ?? '',
                    'days' => (string) (int) $day->diffInDays($contract->end_date->startOfDay()),
                ], route('dashboard')));
                $ledger->markDelivered(self::LEDGER_KIND, $manager->id, [$contract->id], $day);
                $sent++;
            } catch (Throwable $e) {
                $ledger->release(self::LEDGER_KIND, $manager->id, [$contract->id], $day);
                report($e);
            }
        }

        $this->info("Sent {$sent} contract expiration warning(s).");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Contract>
     */
    private function expiringContracts(CarbonInterface $day): Collection
    {
        $renewableTypeIds = collect(config('contracts.map_by_id', []))
            ->filter(fn (string $category): bool => in_array($category, ['annual', 'service'], true))
            ->keys()
            ->all();
        $reminderDates = array_map(fn (int $days): string => $day->copy()->addDays($days)->toDateString(), self::REMINDER_DAYS);

        return Contract::query()
            ->select(['id', 'employee_id', 'position_id', 'end_date'])
            ->with([
                'employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar',
                'position:id,direct_manager_id',
            ])
            ->where('auto_renew', true)
            ->where('is_active', true)
            ->whereIn('contract_type_id', $renewableTypeIds)
            ->where(function (Builder $query) use ($reminderDates): void {
                foreach ($reminderDates as $date) {
                    $query->orWhereDate('end_date', $date);
                }
            })
            ->orderBy('end_date')
            ->orderBy('id')
            ->get();
    }
}
