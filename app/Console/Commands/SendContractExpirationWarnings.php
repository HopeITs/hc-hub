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
    /**
     * Exact reminder anchors, in days before the contract ends.
     *
     * The command runs daily, so matching exact dates gives each contract at
     * most one reminder per anchor without adding a separate notification log.
     *
     * @var array<int, int>
     */
    private const REMINDER_DAYS = [30, 7];

    protected $signature = 'contracts:send-expiration-warnings';

    protected $description = 'Send direct managers scheduled warnings for auto-renewable contracts nearing expiration';

    public function handle(): int
    {
        $contracts = $this->expiringContracts();
        $sent = 0;

        $contracts
            ->groupBy(fn (Contract $contract): string => implode(':', [
                $this->directManagerUser($contract)?->id ?? 'none',
                $contract->end_date ? today()->diffInDays($contract->end_date) : 0,
            ]))
            ->filter(fn (Collection $group): bool => $this->directManagerUser($group->first()) !== null)
            ->each(function (Collection $group) use (&$sent): void {
                $manager = $this->directManagerUser($group->first());

                if (! $manager) {
                    return;
                }

                $manager->notify(new ContractsExpiringAutoRenewWarning(
                    $group,
                    $this->hrEmailsForContracts($group),
                    (int) today()->diffInDays($group->first()->end_date),
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
        $reminderDates = collect(self::REMINDER_DAYS)
            ->map(fn (int $days): string => today()->addDays($days)->toDateString())
            ->all();

        return Contract::query()
            ->with([
                'employee.city',
                'position.directManager.activeContracts.employee.account',
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
