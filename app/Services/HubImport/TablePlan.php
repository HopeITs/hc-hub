<?php

namespace App\Services\HubImport;

/**
 * One table of an import: the rows to write, already transformed, and what
 * writing them will do to the target.
 */
final class TablePlan
{
    /**
     * @param  list<string>  $columns  columns written (source ∩ hub, plus `uuid` where the hub has one)
     * @param  list<string>  $key  the hub table's primary key columns
     * @param  list<string>  $deferred  foreign keys written in a second pass (self references, users ⇄ employees)
     * @param  list<array<string, mixed>>  $rows
     * @param  list<int|string>  $existingIds  ids of these rows already in the hub (an earlier run)
     * @param  list<string>  $pruneIds  ids an earlier run imported that the source no longer has
     */
    public function __construct(
        public readonly string $table,
        public readonly string $sourceTable,
        public readonly array $columns,
        public readonly array $key,
        public readonly array $deferred,
        public readonly array $rows,
        public readonly int $sourceCount,
        public readonly array $existingIds = [],
        public readonly array $pruneIds = [],
    ) {}

    /** Does the table have a single `id` key (an entity, tracked in entity_xref)? */
    public function isEntity(): bool
    {
        return $this->key === ['id'];
    }

    /**
     * @return list<int|string>
     */
    public function ids(): array
    {
        /** @var list<int|string> */
        return array_column($this->rows, 'id');
    }
}
