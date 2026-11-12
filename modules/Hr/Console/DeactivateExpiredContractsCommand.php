<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Contracts\PositionSeats;

/**
 * Daily (HR's `deactivate:contract`): a contract still marked active after
 * its end date goes inactive, and a seat it held on its position is given
 * back through PositionSeats, the same path a termination takes. An ended
 * draft never held a seat, so none is freed for it. Scheduled by
 * HrServiceProvider before the auto-renewals, which pick up what this ended.
 */
class DeactivateExpiredContractsCommand extends Command
{
    protected $signature = 'contracts:deactivate-expired';

    protected $description = 'Switch off contracts past their end date and free their seats';

    public function handle(): int
    {
        $deactivated = 0;

        Contract::query()
            ->select(['id'])
            ->where('is_active', true)
            ->whereDate('end_date', '<', today()->toDateString())
            ->chunkById(100, function (Collection $contracts) use (&$deactivated): void {
                foreach ($contracts as $contract) {
                    if ($this->deactivate((int) $contract->id)) {
                        $deactivated++;
                    }
                }
            });

        $this->components->info("Deactivated {$deactivated} contract(s).");

        return self::SUCCESS;
    }

    private function deactivate(int $contractId): bool
    {
        return DB::transaction(function () use ($contractId): bool {
            $contract = Contract::query()->whereKey($contractId)->lockForUpdate()->first();

            if ($contract === null || ! $contract->is_active || $contract->end_date === null || $contract->end_date->gte(today())) {
                return false;
            }

            $held = PositionSeats::holdsSeat($contract->type, true);

            $contract->forceFill(['is_active' => false])->save();

            if ($held) {
                PositionSeats::move((int) $contract->position_id, null);
            }

            return true;
        });
    }
}
