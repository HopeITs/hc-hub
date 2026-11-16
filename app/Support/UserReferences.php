<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Which rows point at a login: every column with a foreign key to users.id,
 * plus its role grants. Read only. Used to show what a login carries and to
 * refuse cancelling one that still carries anything but roles.
 */
class UserReferences
{
    /**
     * @var list<array{table: string, column: string}>|null
     */
    private ?array $columns = null;

    /**
     * @return array<string, int> "table.column" => rows, only those above zero
     */
    public function countsFor(int $userId): array
    {
        $counts = [];

        foreach ($this->columns() as $reference) {
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
    public static function describe(array $counts): string
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

    /**
     * Every column with a foreign key to users.id.
     *
     * @return list<array{table: string, column: string}>
     */
    private function columns(): array
    {
        return $this->columns ??= array_values(array_map(
            fn (stdClass $row): array => ['table' => (string) $row->table_name, 'column' => (string) $row->column_name],
            DB::select(
                'SELECT table_name AS table_name, column_name AS column_name FROM information_schema.key_column_usage
                 WHERE table_schema = DATABASE() AND referenced_table_name = ? AND referenced_column_name = ?
                 ORDER BY table_name, column_name',
                ['users', 'id'],
            ),
        ));
    }
}
