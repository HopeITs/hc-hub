<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractsAutoRenewedSummary;
use App\Services\ContractAutoRenewalService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProcessContractAutoRenewals extends Command
{
    protected $signature = 'contracts:process-auto-renewals';

    protected $description = 'Deactivate expired auto-renewable contracts and create their successors';

    public function handle(ContractAutoRenewalService $renewals): int
    {
        $contracts = $renewals->process();

        $this->notifyGovernorateHr($contracts);

        $this->info('Renewed '.$contracts->count().' contract(s).');

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, Contract>  $contracts
     */
    private function notifyGovernorateHr(Collection $contracts): void
    {
        $contracts
            ->loadMissing('employee.city')
            ->groupBy(fn (Contract $contract): ?int => $contract->employee?->city_id)
            ->filter(fn (Collection $group, mixed $cityId): bool => $cityId !== null && $group->isNotEmpty())
            ->each(function (Collection $group): void {
                $recipients = $this->hrUsersForCity($group->first());

                if ($recipients->isEmpty()) {
                    return;
                }

                $notification = new ContractsAutoRenewedSummary($group);

                foreach ($recipients as $recipient) {
                    $recipient->notify($notification);
                }
            });
    }

    /**
     * @return Collection<int, User>
     */
    private function hrUsersForCity(Contract $contract): Collection
    {
        $slug = $contract->employee?->city?->slug;

        if (! $slug) {
            return collect();
        }

        return User::query()
            ->whereHas('roles', function (Builder $query) use ($slug): void {
                $query
                    ->where('name', 'HR-'.strtoupper($slug))
                    ->where(config('permission.table_names.model_has_roles').'.is_active', true);
            })
            ->get();
    }
}
