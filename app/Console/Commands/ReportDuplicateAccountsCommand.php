<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\UserReferences;
use Illuminate\Console\Command;
use Modules\Core\Models\User;

/**
 * Lists every employee linked to more than one login, with what each login
 * carries, so a person can decide which one to cancel. Read only: it changes
 * nothing. users.employee_id cannot become unique until this list is empty.
 */
class ReportDuplicateAccountsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hub:duplicate-accounts';

    /**
     * @var string
     */
    protected $description = 'List employees linked to more than one login (read only)';

    public function handle(UserReferences $references): int
    {
        $employeeIds = User::query()
            ->whereNotNull('employee_id')
            ->groupBy('employee_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('employee_id');

        if ($employeeIds->isEmpty()) {
            $this->components->info('No employee is linked to more than one login.');

            return self::SUCCESS;
        }

        foreach ($employeeIds as $employeeId) {
            $users = User::query()->with('roles')->where('employee_id', $employeeId)->orderBy('id')->get();
            $rows = [];

            foreach ($users as $user) {
                $rows[] = [
                    (string) $user->id,
                    $user->email,
                    $user->created_at?->toDateString() ?? '-',
                    $user->last_seen_at === null ? 'never' : (string) $user->last_seen_at,
                    $user->roles->pluck('name')->implode(', ') ?: '-',
                    UserReferences::describe($references->countsFor((int) $user->id)),
                ];
            }

            $this->components->warn("Employee #{$employeeId} has {$users->count()} logins:");
            $this->table(['User', 'Email', 'Created', 'Last seen', 'Roles', 'Rows pointing at it'], $rows);
        }

        $this->components->info('Nothing was changed. Decide which login to cancel, then run hub:cancel-duplicate-account.');

        return self::SUCCESS;
    }
}
