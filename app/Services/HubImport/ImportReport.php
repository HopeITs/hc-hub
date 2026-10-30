<?php

namespace App\Services\HubImport;

/**
 * What one `hub:import` run found and did (or, in a dry run, would do).
 *
 * Counts and ids only: no row values, so no pay figure can reach the console
 * or a log through it.
 */
final class ImportReport
{
    /**
     * @var array<string, array{source: int, skipped: int, inserted: int, updated: int, pruned: int, expected: int, target: int|null}>
     */
    public array $tables = [];

    /** @var array<string, string> source table (without prefix) => why it is not imported */
    public array $skippedSourceTables = [];

    /** @var array<string, list<string>> table => source columns the hub does not have (dropped) */
    public array $droppedColumns = [];

    /** @var array<string, array<string, int>> table => skip reason => rows */
    public array $skippedRows = [];

    /** @var array<string, int> "table.column: stored value -> alias" => rows */
    public array $morphRewrites = [];

    /** @var array<string, int> "table.column: stored value" => rows left as they were (no alias) */
    public array $unmappedMorphTypes = [];

    /** @var list<array{source_table: string, source_id: string, column_name: string, decision: string, old_value: string|null, new_value: string|null, reason: string}> */
    public array $reviewDecisions = [];

    /** @var array<string, int> "table.column -> table.column" => source rows pointing at a missing row */
    public array $orphans = [];

    /** @var list<string> target rows replaced because they collided with a source row (seeded catalogue rows) */
    public array $replacedTargetRows = [];

    /** @var list<string> problems that stop a real run before anything is written */
    public array $blocking = [];

    /** @var list<string> questions for the data owner; reported, never decided by the importer */
    public array $openIssues = [];

    public function __construct(public readonly bool $dryRun) {}

    /**
     * @param  array{source: int, skipped: int, inserted: int, updated: int, pruned: int, expected: int, target: int|null}  $counts
     */
    public function table(string $table, array $counts): void
    {
        $this->tables[$table] = $counts;
    }

    public function skipRow(string $table, string $reason): void
    {
        $this->skippedRows[$table][$reason] = ($this->skippedRows[$table][$reason] ?? 0) + 1;
    }

    public function countsMatch(): bool
    {
        foreach ($this->tables as $counts) {
            if ($counts['target'] !== null && $counts['target'] !== $counts['expected']) {
                return false;
            }
        }

        return true;
    }
}
