<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use Illuminate\Console\Command;
use Modules\Hr\Enums\ContractType;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\EmployeeEntitlement;
use Modules\Hr\Support\Entitlements\Entitlements;

/**
 * Gives the Permissions Library defaults to people already in post.
 *
 * EntitlementHooks adds a position's defaults only when a contract claims a
 * seat, so a library loaded after the staff were hired (EntitlementLibrarySeeder)
 * reaches nobody until this runs. It goes through the same
 * Entitlements::syncForContract() as a new contract, as a renewal (not a
 * return): an item HR or the manager already revoked stays revoked, and
 * nothing is ever revoked here. Safe to run again.
 */
class BackfillEntitlementsCommand extends Command
{
    protected $signature = 'entitlements:backfill
        {--dry-run : Count the people without writing anything}';

    protected $description = 'Add the Permissions Library defaults of their position to everyone holding a contract.';

    public function handle(Entitlements $entitlements): int
    {
        // One contract per person, the latest that holds a seat: the sync takes
        // every other contract running alongside it into account by itself.
        $contracts = Contract::query()
            ->where('is_active', true)
            ->where('type', '!=', ContractType::Draft->value)
            ->whereNull('terminated_at')
            ->whereNull('termination_reason_id')
            ->whereNotNull('employee_id')
            ->whereNotNull('position_id')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->unique('employee_id');

        if ($contracts->isEmpty()) {
            $this->components->info('Nobody holds a contract. Nothing to do.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->components->info("{$contracts->count()} people would get their position's defaults.");
            $this->components->warn('Dry run: nothing was written.');

            return self::SUCCESS;
        }

        $before = EmployeeEntitlement::query()->count();

        foreach ($contracts as $contract) {
            $entitlements->syncForContract($contract, fresh: false);
        }

        $added = EmployeeEntitlement::query()->count() - $before;
        $this->components->info("{$contracts->count()} people checked, {$added} item(s) added as \"To do\".");

        return self::SUCCESS;
    }
}
