<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use stdClass;

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

    public function handle(): int
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

        $references = $this->userReferences();

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
                    $this->describe($this->countReferences($references, (int) $user->id)),
                ];
            }

            $this->components->warn("Employee #{$employeeId} has {$users->count()} logins:");
            $this->table(['User', 'Email', 'Created', 'Last seen', 'Roles', 'Rows pointing at it'], $rows);
        }

        $this->components->info('Nothing was changed. Decide which login to cancel, then move or remove its rows.');

        return self::SUCCESS;
    }

    /**
     * Every column with a foreign key to users.id.
     *
     * @return list<array{table: string, column: string}>
     */
    private function userReferences(): array
    {
        return array_values(array_map(
            fn (stdClass $row): array => ['table' => (string) $row->table_name, 'column' => (string) $row->column_name],
            DB::select(
                'SELECT table_name AS table_name, column_name AS column_name FROM information_schema.key_column_usage
                 WHERE table_schema = DATABASE() AND referenced_table_name = ? AND referenced_column_name = ?
                 ORDER BY table_name, column_name',
                ['users', 'id'],
            ),
        ));
    }

    /**
     * @param  list<array{table: string, column: string}>  $references
     * @return array<string, int> "table.column" => rows, only those above zero
     */
    private function countReferences(array $references, int $userId): array
    {
        $counts = [];

        foreach ($references as $reference) {
            $rows = DB::table($reference['table'])->where($reference['column'], $userId)->count();

            if ($rows > 0) {
                $counts[$reference['table'].'.'.$reference['column']] = $rows;
            }
        }

        $roleRows = DB::table('model_has_roles')->where('model_type', 'user')->where('model_id', $userId)->count();

        if ($roleRows > 0) {
            $counts['model_has_roles'] = $roleRows;
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function describe(array $counts): string
    {
        if ($counts === []) {
            return 'none';
        }

        return implode(', ', array_map(
            fn (string $column, int $rows): string => "{$column}: {$rows}",
            array_keys($counts),
            array_values($counts),
        ));
    }
}
