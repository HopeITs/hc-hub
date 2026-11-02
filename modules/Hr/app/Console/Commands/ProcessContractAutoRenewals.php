<?php

namespace Modules\Hr\Console\Commands;

use App\Models\City;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Hr\Models\Contract;
use Modules\Hr\Notifications\ContractsAutoRenewedSummary;
use Modules\Hr\Services\ContractAutoRenewalService;

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
            ->groupBy(fn (Contract $contract): int => (int) ($contract->employee->city_id ?? 0))
            ->filter(fn (Collection $group, mixed $cityId): bool => (int) $cityId !== 0 && $group->isNotEmpty())
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

        $users = $this->hrUsersForRoles(City::hrRolesForSlug($slug));

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
