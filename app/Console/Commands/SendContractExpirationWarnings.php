<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\User;
use App\Notifications\ContractsExpiringAutoRenewWarning;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SendContractExpirationWarnings extends Command
{
    protected $signature = 'contracts:send-expiration-warnings';

    protected $description = 'Send direct managers warnings for auto-renewable contracts expiring within seven days';

    public function handle(): int
    {
        $contracts = $this->expiringContracts();
        $sent = 0;

        $contracts
            ->groupBy(fn (Contract $contract): ?int => $this->directManagerUser($contract)?->id)
            ->filter(fn (Collection $group, mixed $userId): bool => $userId !== null)
            ->each(function (Collection $group) use (&$sent): void {
                $manager = $this->directManagerUser($group->first());

                if (! $manager) {
                    return;
                }

                $manager->notify(new ContractsExpiringAutoRenewWarning(
                    $group,
                    $this->hrEmailsForContracts($group),
                ));

                $sent++;
            });

        $this->info("Sent {$sent} contract expiration warning notification(s).");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Contract>
     */
    private function expiringContracts(): Collection
    {
        $renewableTypeIds = collect(config('contracts.map_by_id', []))
            ->filter(fn (string $category): bool => in_array($category, ['annual', 'service'], true))
            ->keys()
            ->all();

        return Contract::query()
            ->with([
                'employee.city',
                'position.directManager.activeContracts.employee.account',
            ])
            ->where('auto_renew', true)
            ->where('is_active', true)
            ->whereIn('contract_type_id', $renewableTypeIds)
            ->whereDate('end_date', '>=', today()->toDateString())
            ->whereDate('end_date', '<=', today()->addDays(7)->toDateString())
            ->orderBy('end_date')
            ->orderBy('id')
            ->get();
    }

    private function directManagerUser(Contract $contract): ?User
    {
        return $contract->position?->directManager?->employee()?->account;
    }

    /**
     * @param  Collection<int, Contract>  $contracts
     * @return array<int, string>
     */
    private function hrEmailsForContracts(Collection $contracts): array
    {
        $roles = $contracts
            ->map(fn (Contract $contract): ?string => $contract->employee?->city?->slug)
            ->filter()
            ->map(fn (string $slug): string => 'HR-'.strtoupper($slug))
            ->unique()
            ->values();

        if ($roles->isEmpty()) {
            return [];
        }

        return User::query()
            ->whereHas('roles', function (Builder $query) use ($roles): void {
                $query
                    ->whereIn('name', $roles)
                    ->where(config('permission.table_names.model_has_roles').'.is_active', true);
            })
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
