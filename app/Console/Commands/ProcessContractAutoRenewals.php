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

    protected $description = 'Create successor contracts for expired inactive auto-renewable contracts';

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
        if ($contracts->isEmpty()) {
            return;
        }

        $contracts
            ->each(fn (Contract $contract): Contract => $contract->loadMissing('employee.city'))
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

        $users = $this->hrUsersForRoles(['HR-'.strtoupper($slug)]);

        return $users->isNotEmpty() ? $users : $this->hrUsersForRoles(['HR-SYR']);
    }

    /**
     * @param  array<int, string>  $roles
     * @return Collection<int, User>
     */
    private function hrUsersForRoles(array $roles): Collection
    {
        return User::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', $roles))
            ->whereHas('profile', fn (Builder $query): Builder => $query->where('is_active', true))
            ->get();
    }
}
