<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Import;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Finds rows whose foreign key points at nothing.
 *
 * The import runs with foreign key checks off, so the database does not
 * stop a broken reference; this does, before the import commits. It reads
 * every foreign key of the current schema, so tracking's are checked too
 * (which is what proves the user renumbering). MySQL only.
 */
final class OrphanCheck
{
    public function __construct(private readonly Connection $db) {}

    public static function make(): self
    {
        $connection = DB::connection();

        return new self($connection);
    }

    /**
     * Orphan counts per foreign key, only those above zero.
     *
     * @return array<string, int> "table.column -> parent.column" => rows
     */
    public function foreignKeys(): array
    {
        $keys = $this->db->select(
            'SELECT TABLE_NAME AS t, COLUMN_NAME AS c, REFERENCED_TABLE_NAME AS rt, REFERENCED_COLUMN_NAME AS rc
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL'
        );

        $grammar = $this->db->getQueryGrammar();
        $orphans = [];

        foreach ($keys as $key) {
            $count = (int) $this->db->scalar(sprintf(
                'SELECT COUNT(*) FROM %s AS child WHERE child.%s IS NOT NULL AND NOT EXISTS (SELECT 1 FROM %s AS parent WHERE parent.%s = child.%s)',
                $grammar->wrapTable((string) $key->t),
                $grammar->wrap((string) $key->c),
                $grammar->wrapTable((string) $key->rt),
                $grammar->wrap((string) $key->rc),
                $grammar->wrap((string) $key->c),
            ));

            if ($count > 0) {
                $orphans["{$key->t}.{$key->c} -> {$key->rt}.{$key->rc}"] = $count;
            }
        }

        return $orphans;
    }

    /**
     * Unconstrained user references (morphs and sessions) that point at no user.
     *
     * @return array<string, int>
     */
    public function userReferences(): array
    {
        $checks = [
            'model_has_roles.model_id' => "SELECT COUNT(*) FROM model_has_roles WHERE model_type = 'user' AND model_id NOT IN (SELECT id FROM users)",
            'notifications.notifiable_id' => "SELECT COUNT(*) FROM notifications WHERE notifiable_type = 'user' AND notifiable_id NOT IN (SELECT id FROM users)",
            'notifications.subject_id' => "SELECT COUNT(*) FROM notifications WHERE subject_type = 'user' AND subject_id NOT IN (SELECT id FROM users)",
            'activities.subject_id' => "SELECT COUNT(*) FROM activities WHERE subject_type = 'user' AND subject_id NOT IN (SELECT id FROM users)",
        ];

        return array_filter(array_map(fn (string $sql): int => (int) $this->db->scalar($sql), $checks));
    }
}
