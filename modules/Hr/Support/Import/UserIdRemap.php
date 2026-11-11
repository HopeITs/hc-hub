<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Import;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Modules\Hr\Support\HrTables;

/**
 * Renumbers users so users.id is the HR user id (users.hr_user_id).
 *
 * Tracking numbered its users itself and linked each to HR through
 * hr_user_id. Once accounts live in the shared users table, every module has
 * to agree on one id, and HR's tables already hold HR ids. So each user whose
 * id differs from its hr_user_id takes the HR id, and every column that holds
 * a tracking user id is rewritten through an (old_id, new_id) map:
 *
 * - every foreign key to users.id outside the HR tables (read from the
 *   schema, so a column added later is not missed);
 * - the polymorphic/unconstrained ones: Spatie's model_has_roles and
 *   model_has_permissions, notifications (notifiable and subject),
 *   notification_alert_ledger and activities subjects of type 'user', and
 *   sessions.user_id.
 *
 * Many old ids are the new id of somebody else (65 in the live data), so a
 * plain UPDATE would cross people over or hit a duplicate key. Every column
 * is therefore moved in two steps: first to new_id + OFFSET (free values),
 * then back down by OFFSET.
 *
 * It is done when no user has id != hr_user_id. The renumbering has run on
 * the live data and users.hr_user_id has since been dropped (users is now
 * the one account table), so without that column there is nothing left to
 * do and the remap is a no-op. MySQL only, and it must run with foreign key
 * checks off inside the caller's transaction.
 */
final class UserIdRemap
{
    public const int OFFSET = 1_000_000_000;

    /**
     * Unconstrained columns holding a user id, with the morph type that marks
     * a row as a user's (null when every row is a user's).
     *
     * @var list<array{0: string, 1: string, 2: string|null, 3: string|null}>
     */
    private const array SOFT_COLUMNS = [
        ['model_has_roles', 'model_id', 'model_type', 'user'],
        ['model_has_permissions', 'model_id', 'model_type', 'user'],
        ['notifications', 'notifiable_id', 'notifiable_type', 'user'],
        ['notifications', 'subject_id', 'subject_type', 'user'],
        ['notification_alert_ledger', 'subject_id', 'subject_type', 'user'],
        ['activities', 'subject_id', 'subject_type', 'user'],
        ['sessions', 'user_id', null, null],
    ];

    public function __construct(private readonly Connection $db) {}

    public static function make(): self
    {
        $connection = DB::connection();

        return new self($connection);
    }

    /**
     * How many users still need a new id.
     */
    public function pending(): int
    {
        if (! $this->db->getSchemaBuilder()->hasColumn('users', 'hr_user_id')) {
            return 0;
        }

        return $this->db->table('users')->whereColumn('id', '!=', 'hr_user_id')->count();
    }

    public function isDone(): bool
    {
        return $this->pending() === 0;
    }

    /**
     * Renumber the users that need it.
     *
     * @return array{users: int, rows: array<string, int>} users renumbered and rows rewritten per table.column
     */
    public function run(): array
    {
        $pending = $this->pending();

        if ($pending === 0) {
            return ['users' => 0, 'rows' => []];
        }

        $this->db->statement('DROP TEMPORARY TABLE IF EXISTS hub_user_remap');
        $this->db->statement('CREATE TEMPORARY TABLE hub_user_remap (old_id BIGINT UNSIGNED NOT NULL PRIMARY KEY, new_id BIGINT UNSIGNED NOT NULL UNIQUE)');
        $this->db->statement('INSERT INTO hub_user_remap (old_id, new_id) SELECT id, hr_user_id FROM users WHERE id <> hr_user_id');

        $rows = [];

        foreach ($this->columns() as [$table, $column, $typeColumn, $type]) {
            $rows["{$table}.{$column}"] = $this->move($table, $column, $typeColumn, $type);
        }

        $this->move('users', 'id', null, null);

        $this->db->statement('DROP TEMPORARY TABLE IF EXISTS hub_user_remap');

        return ['users' => $pending, 'rows' => $rows];
    }

    /**
     * Every column to rewrite, as [table, column, morph type column, morph type].
     *
     * @return list<array{0: string, 1: string, 2: string|null, 3: string|null}>
     */
    public function columns(): array
    {
        $skip = [...HrTables::targets(), 'users'];

        $foreignKeys = $this->db->select(
            'SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = ? AND REFERENCED_COLUMN_NAME = ?
             ORDER BY TABLE_NAME, COLUMN_NAME',
            ['users', 'id'],
        );

        $columns = [];

        foreach ($foreignKeys as $key) {
            $table = (string) $key->t;

            if (! in_array($table, $skip, true)) {
                $columns[] = [$table, (string) $key->c, null, null];
            }
        }

        foreach (self::SOFT_COLUMNS as $column) {
            if ($this->db->getSchemaBuilder()->hasColumn($column[0], $column[1])) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * Move one column through the map in two steps, returning the rows moved.
     */
    private function move(string $table, string $column, ?string $typeColumn, ?string $type): int
    {
        $grammar = $this->db->getQueryGrammar();
        $t = $grammar->wrapTable($table);
        $c = $grammar->wrap($column);
        $where = $typeColumn === null ? '' : ' AND t.'.$grammar->wrap($typeColumn).' = ?';
        $bindings = $typeColumn === null ? [] : [$type];

        $moved = $this->db->update(
            "UPDATE {$t} AS t JOIN hub_user_remap AS m ON t.{$c} = m.old_id SET t.{$c} = m.new_id + ".self::OFFSET.' WHERE 1 = 1'.$where,
            $bindings,
        );

        $this->db->update(
            "UPDATE {$t} SET {$c} = {$c} - ".self::OFFSET." WHERE {$c} >= ".self::OFFSET,
        );

        return $moved;
    }
}
