<?php

namespace App\Services\HubImport;

use App\Support\MorphMap;
use App\Traits\HasPublicUuid;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Hr\Services\SalaryCipher;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Copies hc-hr's database (`hr_source`, tables prefixed `hchr_`) into the hub
 * (plan phase 3.1, HR part).
 *
 * - Ids are kept. Every entity row gets an `entity_xref` row (Core shape:
 *   source system/table/id → entity type/id/uuid).
 * - Morph class names (`App\Models\Employee`) become the aliases of
 *   App\Support\MorphMap; permission names get the module prefix of migration
 *   2026_10_29_000002 (same frozen mapping); shared master tables get a
 *   UUIDv7 `uuid` that, once stored, never changes.
 * - Idempotent: rows are upserted by primary key, pivot rows are replaced per
 *   imported owner, rows an earlier run imported that the source has since
 *   lost are pruned. A second run changes nothing.
 * - Pay figures travel only inside their encrypted columns, copied as the
 *   ciphertext envelope (never decrypted, so SALARY_ENCRYPTION_KEY keeps its
 *   meaning: the hub must run with HR's key). A plaintext figure anywhere
 *   blocks the run.
 *
 * Everything is planned (read and transformed) before anything is written; a
 * dry run stops after the plan, and a real run writes in one transaction.
 */
final class HrImporter
{
    public const SOURCE_SYSTEM = 'hr';

    public const CONNECTION = 'hr_source';

    /**
     * Hub tables filled from the source, in load order: every table after the
     * tables its foreign keys point to. The foreign keys that cannot be ordered
     * (self references, employees.last_saved_by ⇄ users.employee_id) are
     * found at run time and written in a second pass.
     *
     * @var list<string>
     */
    public const TABLES = [
        'cities', 'centers', 'programs', 'departments', 'job_levels', 'contract_types', 'termination_reasons',
        'leave_types', 'annual_leave_rules', 'course_categories', 'education_fields', 'checklist_items',
        'employees', 'users',
        'positions', 'contracts', 'contract_pay_tiers', 'evaluations', 'badges',
        'leave_balances', 'leave_requests', 'leave_request_notes',
        'courses', 'educations', 'experiences', 'relatives', 'employee_items', 'employee_notes', 'profile_change_requests',
        'checklists', 'default_deliverables', 'holidays', 'holiday_project', 'birthday_holiday_choices',
        'folders', 'folder_user', 'news',
        'permissions', 'roles', 'role_has_permissions', 'model_has_roles', 'model_has_permissions',
        'media', 'activity_log', 'notifications', 'notification_deliveries',
        'saved_table_views', 'task_attempts', 'in_app_feedback',
    ];

    /** @var list<string> runtime tables that start fresh in the hub (plan section 3) */
    public const FRESH_TABLES = ['cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens', 'migrations'];

    /** @var array<string, array<string, string>> table => morph type column => its id column */
    private const MORPH_COLUMNS = [
        'model_has_roles' => ['model_type' => 'model_id'],
        'model_has_permissions' => ['model_type' => 'model_id'],
        'media' => ['model_type' => 'model_id'],
        'activity_log' => ['subject_type' => 'subject_id', 'causer_type' => 'causer_id'],
        'notifications' => ['notifiable_type' => 'notifiable_id'],
        'news' => ['newsable_type' => 'newsable_id'],
    ];

    /**
     * Tables without an `id`: their rows are replaced per imported owner.
     *
     * @var array<string, array{0: string, 1: string, 2: string|null}> table => [owner column, owner table, morph alias the rows must carry]
     */
    private const PIVOTS = [
        'role_has_permissions' => ['role_id', 'roles', null],
        'model_has_roles' => ['model_id', 'users', 'user'],
        'model_has_permissions' => ['model_id', 'users', 'user'],
        'folder_user' => ['folder_id', 'folders', null],
    ];

    /**
     * Catalogue tables the hub's own migrations seed. A seeded row that has
     * the name of a source row under another id is replaced by the source row.
     *
     * @var array<string, array{0: list<string>, 1: array<string, string>}> table => [natural key, pivot table => column pointing at it]
     */
    private const SEEDED_CATALOGUES = [
        'permissions' => [['name', 'guard_name'], ['role_has_permissions' => 'permission_id', 'model_has_permissions' => 'permission_id']],
        'roles' => [['name', 'guard_name'], ['role_has_permissions' => 'role_id', 'model_has_roles' => 'role_id']],
    ];

    /** @var array<string, list<string>> pay columns stored as SalaryCipher envelopes */
    private const ENCRYPTED_PAY_COLUMNS = ['contracts' => ['salary'], 'contract_pay_tiers' => ['amount']];

    /** @var array<string, list<string>> pay columns stored in clear; a figure in one blocks the run */
    private const PLAINTEXT_PAY_COLUMNS = ['badges' => ['amount', 'current_salary', 'new_salary']];

    /** @var array<string, list<string>> Core fix #2: names with stray spaces */
    private const TRIMMED_COLUMNS = ['cities' => ['name_en', 'name_ar']];

    /** @var array<string, array<int, array<string, string>>> decided replacements: table => id => column => value */
    private const DECIDED_VALUES = [
        'centers' => [15 => ['name_ar' => 'ستادي زون المحطة']],
    ];

    /**
     * Hub columns the source does not have, set by the importer.
     *
     * @var array<string, list<string>>
     */
    private const IMPORTER_SET_COLUMNS = ['programs' => ['parent_id']];

    /**
     * Self references written in the first pass: the rows are ordered so a
     * parent comes before its children (a unique index depends on the value).
     *
     * @var array<string, string>
     */
    private const ORDERED_SELF_REFERENCES = ['programs' => 'parent_id'];

    private Connection $source;

    private ImportReport $report;

    /** @var array<string, string> permission name before the prefix => after */
    private array $permissionRenames = [];

    /** @var array<int, int> sub-program id => parent program id */
    private array $programParents = [];

    /** @var array<int|string, true> source permission ids that are not imported */
    private array $skippedPermissionIds = [];

    /** @var array<string, string|null> stored morph type => alias (null: unknown) */
    private array $morphAliases = [];

    public function __construct(private readonly SalaryCipher $cipher) {}

    public function run(bool $dryRun): ImportReport
    {
        $this->source = SourceConnection::open(self::CONNECTION);
        $this->report = new ImportReport($dryRun);
        $this->permissionRenames = $this->frozenPermissionRenames();
        $this->programParents = $this->frozenProgramParents();
        $this->skippedPermissionIds = [];
        $this->morphAliases = [];

        $this->classifySourceTables();

        $plans = [];
        foreach (self::TABLES as $table) {
            $plan = $this->plan($table);

            if ($plan !== null) {
                $plans[$table] = $plan;
            }
        }

        $this->reportOrphans();
        $this->reportOpenIssues();

        if (! $dryRun && $this->report->blocking === []) {
            DB::transaction(fn () => $this->write($plans));
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        foreach ($plans as $plan) {
            $this->reportTable($plan, $dryRun || $this->report->blocking !== []);
        }

        return $this->report;
    }

    /**
     * Source tables that are not imported, and why.
     */
    private function classifySourceTables(): void
    {
        $prefix = $this->source->getTablePrefix();

        foreach ($this->source->getSchemaBuilder()->getTables() as $table) {
            $name = Str::after($table['name'], $prefix);

            if (in_array($name, self::TABLES, true) || ! str_starts_with($table['name'], $prefix)) {
                continue;
            }

            $this->report->skippedSourceTables[$name] = match (true) {
                str_contains($name, '_bak_') => 'backup copy (decided: skip)',
                in_array($name, self::FRESH_TABLES, true) => 'runtime table, starts fresh',
                Schema::hasTable($name) => 'not in the import list',
                default => 'no such table in the hub ('.$this->source->table($name)->count().' rows)',
            };
        }
    }

    private function plan(string $table): ?TablePlan
    {
        $sourceSchema = $this->source->getSchemaBuilder();

        if (! $sourceSchema->hasTable($table)) {
            $this->report->blocking[] = "Source table {$this->sourceTable($table)} is missing.";

            return null;
        }

        if (! Schema::hasTable($table)) {
            $this->report->blocking[] = "Hub table {$table} is missing (run the migrations first).";

            return null;
        }

        $sourceColumns = $sourceSchema->getColumnListing($table);
        $hubColumns = Schema::getColumnListing($table);
        $columns = array_values(array_intersect($hubColumns, $sourceColumns));

        if (in_array($table, $this->uuidTables(), true) && ! in_array('uuid', $columns, true)) {
            $columns[] = 'uuid';
        }

        foreach (self::IMPORTER_SET_COLUMNS[$table] ?? [] as $column) {
            if (! in_array($column, $columns, true)) {
                $columns[] = $column;
            }
        }

        $dropped = array_values(array_diff($sourceColumns, $hubColumns));
        if ($dropped !== []) {
            $this->report->droppedColumns[$table] = $dropped;
        }

        $key = $this->primaryKey($table);
        $rows = [];
        $sourceCount = 0;
        $blankPayFigures = [];

        foreach ($this->source->table($table)->orderBy($key[0])->cursor() as $sourceRow) {
            $sourceCount++;
            $row = $this->transform($table, (array) $sourceRow, $blankPayFigures);

            if ($row !== null) {
                $rows[] = array_map(fn (string $column): mixed => $row[$column] ?? null, array_combine($columns, $columns));
            }
        }

        foreach ($blankPayFigures as $problem => $count) {
            $this->report->blocking[] = "{$count} {$problem}";
        }

        if ($table === 'programs') {
            $rows = $this->parentsFirst($rows);
            $this->reportDuplicateTopLevelCodes($rows);
        }

        $deferred = $this->deferredColumns($table);
        $this->reportNullsTheHubRefuses($table, $rows, array_diff($columns, [...$deferred, 'uuid']));

        $plan = new TablePlan(
            table: $table,
            sourceTable: $this->sourceTable($table),
            columns: $columns,
            key: $key,
            deferred: $deferred,
            rows: $rows,
            sourceCount: $sourceCount,
        );

        if (! $plan->isEntity()) {
            return $plan;
        }

        $ids = $plan->ids();
        $existing = [];
        foreach (array_chunk($ids, 1000) as $chunk) {
            array_push($existing, ...DB::table($table)->whereIn('id', $chunk)->pluck('id')->all());
        }

        $imported = DB::table('entity_xref')
            ->where('source_system', self::SOURCE_SYSTEM)
            ->where('source_table', $plan->sourceTable)
            ->pluck('source_id')
            ->all();

        return new TablePlan(
            table: $table,
            sourceTable: $plan->sourceTable,
            columns: $columns,
            key: $key,
            deferred: $plan->deferred,
            rows: $rows,
            sourceCount: $sourceCount,
            existingIds: $existing,
            pruneIds: array_values(array_diff($imported, array_map('strval', $ids))),
        );
    }

    /**
     * Programs ordered so every parent is written before its sub-programs.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function parentsFirst(array $rows): array
    {
        usort($rows, fn (array $a, array $b): int => [$a['parent_id'] !== null, $a['id']] <=> [$b['parent_id'] !== null, $b['id']]);

        return $rows;
    }

    /**
     * Top-level program codes must be unique (programs.top_level_code).
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function reportDuplicateTopLevelCodes(array $rows): void
    {
        $codes = [];

        foreach ($rows as $row) {
            if ($row['parent_id'] === null) {
                $codes[(string) $row['code']][] = $row['id'];
            }
        }

        foreach ($codes as $code => $ids) {
            if (count($ids) > 1) {
                $this->report->blocking[] = 'Top-level programs '.implode(', ', $ids)." share code {$code}; programs.code is unique among top-level programs.";
            }
        }
    }

    /**
     * Source NULLs in a column the hub declares NOT NULL (the source schema
     * was loosened by hand; an explicit NULL fails even where there is a default): the insert would fail, so the
     * run is blocked and the rows are named for the owner to decide.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<int, string>  $columns
     */
    private function reportNullsTheHubRefuses(string $table, array $rows, array $columns): void
    {
        foreach (Schema::getColumns($table) as $column) {
            if ($column['nullable'] || $column['auto_increment'] || ! in_array($column['name'], $columns, true)) {
                continue;
            }

            $ids = array_column(array_filter($rows, fn (array $row): bool => $row[$column['name']] === null), 'id');

            if ($ids !== []) {
                $this->report->blocking[] = count($ids)." {$table} row(s) have no {$column['name']} in the source, but the hub column is NOT NULL (ids ".implode(', ', $ids).').';
            }
        }
    }

    /**
     * One source row as the hub stores it, or null when it is not imported.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, int>  $payProblems  problem => rows (counts only: values never leave this method)
     * @return array<string, mixed>|null
     */
    private function transform(string $table, array $row, array &$payProblems): ?array
    {
        if ($table === 'permissions') {
            $name = (string) $row['name'];
            $renamed = $this->permissionRenames[$name] ?? (in_array($name, $this->permissionRenames, true) ? $name : null);

            if ($renamed === null) {
                $this->skippedPermissionIds[$row['id']] = true;
                $this->report->skipRow($table, 'not in the hub permission catalogue');

                return null;
            }

            $row['name'] = $renamed;
        }

        if (isset($row['permission_id']) && isset($this->skippedPermissionIds[$row['permission_id']])) {
            $this->report->skipRow($table, 'grants a permission that is not imported');

            return null;
        }

        foreach (self::MORPH_COLUMNS[$table] ?? [] as $typeColumn => $idColumn) {
            $row[$typeColumn] = $this->morphAlias($table, $typeColumn, $row[$typeColumn] ?? null);
        }

        foreach (self::ENCRYPTED_PAY_COLUMNS[$table] ?? [] as $column) {
            $value = $row[$column] ?? null;

            if ($value !== null && $value !== '' && ! $this->cipher->looksEncrypted((string) $value)) {
                $problem = "{$table}.{$column} value(s) are not encrypted: pay figures may only be copied as ciphertext.";
                $payProblems[$problem] = ($payProblems[$problem] ?? 0) + 1;
            }
        }

        foreach (self::PLAINTEXT_PAY_COLUMNS[$table] ?? [] as $column) {
            if (($row[$column] ?? null) !== null) {
                $problem = "{$table}.{$column} value(s) hold a pay figure outside the encrypted columns.";
                $payProblems[$problem] = ($payProblems[$problem] ?? 0) + 1;
            }
        }

        foreach (self::TRIMMED_COLUMNS[$table] ?? [] as $column) {
            $value = $row[$column] ?? null;

            if (is_string($value) && trim($value) !== $value) {
                $this->decide($table, $row['id'], $column, 'trim', 'Core fix #2: stray spaces around the name.', $value, trim($value));
                $row[$column] = trim($value);
            }
        }

        $id = is_numeric($row['id'] ?? null) ? (int) $row['id'] : 0;

        foreach (self::DECIDED_VALUES[$table][$id] ?? [] as $column => $value) {
            if (($row[$column] ?? null) !== $value) {
                $old = $row[$column] ?? null;
                $this->decide($table, $id, $column, 'set', 'Core fix #9 (decided 2026-10-30).', is_scalar($old) ? (string) $old : null, $value);
                $row[$column] = $value;
            }
        }

        if ($table === 'programs' && isset($this->programParents[$id])) {
            $row['parent_id'] = $this->programParents[$id];
            $this->decide($table, $id, 'parent_id', 'set', 'Core fix #10 (decided 2026-10-30): a sub-program filed as a program; it may share its parent code.', null, (string) $row['parent_id']);
        }

        if ($table === 'cities' && ($row['slug'] ?? null) === 'SYR') {
            $this->decide($table, $row['id'], '', 'keep', 'Core fix #3 (decided 2026-10-30): "Syria" is the all-governorates row behind the HR-SYR scope; record it for the Core as type country/all, never delete it.', null, null);
        }

        return $row;
    }

    /**
     * @param  array<string, TablePlan>  $plans
     */
    private function write(array $plans): void
    {
        foreach ($plans as $plan) {
            try {
                if (isset(self::SEEDED_CATALOGUES[$plan->table])) {
                    $this->replaceCollidingSeededRows($plan);
                }

                $plan->isEntity() ? $this->upsertEntities($plan) : $this->replacePivotRows($plan);
            } catch (QueryException $exception) {
                // The query's bindings are row values (personal data, pay envelopes): never echo them.
                throw new RuntimeException("hub:import could not write {$plan->table}: ".($exception->errorInfo[2] ?? $exception->getCode()));
            }
        }

        foreach ($plans as $plan) {
            $this->writeDeferredColumns($plan);
        }

        foreach (array_reverse($plans) as $plan) {
            $this->prune($plan);
        }

        foreach ($this->report->reviewDecisions as $decision) {
            DB::table('review_decisions')->updateOrInsert(
                [
                    'source_system' => self::SOURCE_SYSTEM,
                    'source_table' => $decision['source_table'],
                    'source_id' => $decision['source_id'],
                    'column_name' => $decision['column_name'],
                ],
                [
                    'decision' => $decision['decision'],
                    'old_value' => $decision['old_value'],
                    'new_value' => $decision['new_value'],
                    'reason' => $decision['reason'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function upsertEntities(TablePlan $plan): void
    {
        $hasUuid = in_array('uuid', $plan->columns, true);
        $storedUuids = $hasUuid ? $this->storedUuids($plan) : [];
        $entityType = $this->entityTypes()[$plan->table] ?? $plan->table;
        $now = now();

        foreach (array_chunk($plan->rows, 200) as $chunk) {
            $rows = [];
            $xref = [];

            foreach ($chunk as $row) {
                if ($hasUuid) {
                    $row['uuid'] = $storedUuids[$row['id']] ?? (filled($row['uuid']) ? $row['uuid'] : (string) Str::uuid7());
                }

                foreach ($plan->deferred as $column) {
                    $row[$column] = null;
                }

                $rows[] = $row;
                $xref[] = [
                    'source_system' => self::SOURCE_SYSTEM,
                    'source_table' => $plan->sourceTable,
                    'source_id' => (string) $row['id'],
                    'entity_type' => $entityType,
                    'entity_id' => (string) $row['id'],
                    'uuid' => $hasUuid ? $row['uuid'] : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table($plan->table)->upsert($rows, ['id'], array_values(array_diff($plan->columns, ['id'])));
            DB::table('entity_xref')->upsert(
                $xref,
                ['source_system', 'source_table', 'source_id'],
                ['entity_type', 'entity_id', 'uuid', 'updated_at'],
            );
        }
    }

    /**
     * The hub's uuid for rows that already have one: it never changes.
     *
     * @return array<int|string, string>
     */
    private function storedUuids(TablePlan $plan): array
    {
        $uuids = [];

        foreach (array_chunk($plan->ids(), 1000) as $chunk) {
            foreach (DB::table($plan->table)->whereIn('id', $chunk)->whereNotNull('uuid')->pluck('uuid', 'id') as $id => $uuid) {
                $uuids[$id] = (string) $uuid;
            }
        }

        return $uuids;
    }

    private function replacePivotRows(TablePlan $plan): void
    {
        [$ownerColumn, , $alias] = self::PIVOTS[$plan->table]
            ?? throw new RuntimeException("{$plan->table} has no id and no pivot scope.");

        foreach (array_chunk($this->pivotOwnerIds($plan), 1000) as $chunk) {
            DB::table($plan->table)
                ->whereIn($ownerColumn, $chunk)
                ->when($alias !== null, fn ($query) => $query->where('model_type', $alias))
                ->delete();
        }

        foreach (array_chunk($plan->rows, 200) as $chunk) {
            DB::table($plan->table)->insertOrIgnore($chunk);
        }
    }

    /**
     * The owners whose pivot rows the source decides: every source owner row,
     * plus owners the rows name that the source lacks (orphans are copied too).
     *
     * @return list<mixed>
     */
    private function pivotOwnerIds(TablePlan $plan): array
    {
        [$ownerColumn, $ownerTable] = self::PIVOTS[$plan->table];

        return array_values(array_unique([
            ...$this->source->table($ownerTable)->pluck('id')->all(),
            ...array_column($plan->rows, $ownerColumn),
        ]));
    }

    /**
     * A catalogue row the hub's migrations seeded under another id than the
     * source's row of the same name gives way to the source row (with the
     * grants it was seeded with).
     */
    private function replaceCollidingSeededRows(TablePlan $plan): void
    {
        [$naturalKey, $pivots] = self::SEEDED_CATALOGUES[$plan->table];

        foreach ($plan->rows as $row) {
            $query = DB::table($plan->table)->where('id', '!=', $row['id']);

            foreach ($naturalKey as $column) {
                $query->where($column, $row[$column]);
            }

            foreach ($query->pluck('id') as $collidingId) {
                foreach ($pivots as $pivot => $column) {
                    DB::table($pivot)->where($column, $collidingId)->delete();
                }

                DB::table($plan->table)->where('id', $collidingId)->delete();
                $this->report->replacedTargetRows[] = "{$plan->table} #{$collidingId} ({$row['name']}) → source #{$row['id']}";
            }
        }
    }

    private function writeDeferredColumns(TablePlan $plan): void
    {
        if ($plan->deferred === []) {
            return;
        }

        foreach ($plan->rows as $row) {
            $values = array_filter(
                array_intersect_key($row, array_flip($plan->deferred)),
                fn (mixed $value): bool => $value !== null,
            );

            if ($values !== []) {
                DB::table($plan->table)->where('id', $row['id'])->update($values);
            }
        }
    }

    private function prune(TablePlan $plan): void
    {
        foreach (array_chunk($plan->pruneIds, 1000) as $chunk) {
            DB::table($plan->table)->whereIn('id', $chunk)->delete();
            DB::table('entity_xref')
                ->where('source_system', self::SOURCE_SYSTEM)
                ->where('source_table', $plan->sourceTable)
                ->whereIn('source_id', $chunk)
                ->delete();
        }
    }

    private function reportTable(TablePlan $plan, bool $planned): void
    {
        $expected = count($plan->rows);

        $this->report->table($plan->table, [
            'source' => $plan->sourceCount,
            'skipped' => $plan->sourceCount - $expected,
            'inserted' => $expected - count($plan->existingIds),
            'updated' => count($plan->existingIds),
            'pruned' => count($plan->pruneIds),
            'expected' => $expected,
            'target' => $planned ? null : $this->targetCount($plan),
        ]);
    }

    /** Hub rows that correspond to the imported source rows, after the write. */
    private function targetCount(TablePlan $plan): int
    {
        if ($plan->isEntity()) {
            $count = 0;
            foreach (array_chunk($plan->ids(), 1000) as $chunk) {
                $count += DB::table($plan->table)->whereIn('id', $chunk)->count();
            }

            return $count;
        }

        [$ownerColumn, , $alias] = self::PIVOTS[$plan->table];
        $count = 0;

        foreach (array_chunk($this->pivotOwnerIds($plan), 1000) as $chunk) {
            $count += DB::table($plan->table)
                ->whereIn($ownerColumn, $chunk)
                ->when($alias !== null, fn ($query) => $query->where('model_type', $alias))
                ->count();
        }

        return $count;
    }

    /**
     * Source rows whose foreign key, or polymorphic target, points at a row
     * that does not exist (counted on the source, before any cleaning).
     */
    private function reportOrphans(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! $this->source->getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                if (count($foreignKey['columns']) !== 1 || ! in_array($foreignKey['foreign_table'], self::TABLES, true)) {
                    continue;
                }

                $column = $foreignKey['columns'][0];

                if (! $this->source->getSchemaBuilder()->hasColumn($table, $column)) {
                    continue;
                }
                $count = $this->source->table($table)
                    ->whereNotNull($column)
                    ->whereNotIn($column, $this->source->table($foreignKey['foreign_table'])->select($foreignKey['foreign_columns'][0]))
                    ->count();

                if ($count > 0) {
                    $this->report->orphans["{$table}.{$column} -> {$foreignKey['foreign_table']}"] = $count;
                }
            }

            foreach (self::MORPH_COLUMNS[$table] ?? [] as $typeColumn => $idColumn) {
                foreach ($this->source->table($table)->whereNotNull($typeColumn)->distinct()->pluck($typeColumn) as $stored) {
                    $targetTable = array_flip($this->entityTypes())[$this->morphAliases[(string) $stored] ?? ''] ?? null;

                    if ($targetTable === null || ! in_array($targetTable, self::TABLES, true)) {
                        continue;
                    }

                    $count = $this->source->table($table)
                        ->where($typeColumn, $stored)
                        ->whereNotIn($idColumn, $this->source->table($targetTable)->select('id'))
                        ->count();

                    if ($count > 0) {
                        $this->report->orphans["{$table}.{$idColumn} ({$this->morphAliases[(string) $stored]}) -> {$targetTable}"] = $count;
                    }
                }
            }
        }
    }

    /**
     * Data questions the owner decides; the importer copies the rows as they are.
     */
    private function reportOpenIssues(): void
    {
        $usersWithoutEmployee = $this->source->table('users')
            ->where(fn ($query) => $query
                ->whereNull('employee_id')
                ->orWhereNotIn('employee_id', $this->source->table('employees')->select('id')))
            ->orderBy('id')
            ->pluck('id');

        $this->report->openIssues[] = $usersWithoutEmployee->isEmpty()
            ? 'Core fix #30: no user without an employee in this source (the Core inventory counted 1; re-check on the cutover copy).'
            : 'Core fix #30: users without an employee: '.$usersWithoutEmployee->implode(', ').' (the hub requires one per user; fix before cutover).';
    }

    /**
     * Records a cleaning choice for review_decisions. Never called for a pay
     * column, so no figure can reach the log.
     */
    private function decide(string $table, mixed $id, string $column, string $decision, string $reason, ?string $old, ?string $new): void
    {
        $this->report->reviewDecisions[] = [
            'source_table' => $this->sourceTable($table),
            'source_id' => is_scalar($id) ? (string) $id : '',
            'column_name' => $column,
            'decision' => $decision,
            'old_value' => $old,
            'new_value' => $new,
            'reason' => $reason,
        ];
    }

    private function morphAlias(string $table, string $column, mixed $stored): mixed
    {
        if (! is_string($stored)) {
            return $stored;
        }

        if (! array_key_exists($stored, $this->morphAliases)) {
            $this->morphAliases[$stored] = $this->knownMorphTypes()[$stored] ?? null;
        }

        $alias = $this->morphAliases[$stored];

        if ($alias === null) {
            $label = "{$table}.{$column}: {$stored}";
            $this->report->unmappedMorphTypes[$label] = ($this->report->unmappedMorphTypes[$label] ?? 0) + 1;

            return $stored;
        }

        if ($alias !== $stored) {
            $label = "{$table}.{$column}: {$stored} -> {$alias}";
            $this->report->morphRewrites[$label] = ($this->report->morphRewrites[$label] ?? 0) + 1;
        }

        return $alias;
    }

    /**
     * Every stored form of a morph type → its alias: the alias itself, the
     * current class, and the `App\Models\X` name hc-hr wrote.
     *
     * @return array<string, string>
     */
    private function knownMorphTypes(): array
    {
        $types = [];

        foreach (MorphMap::MAP as $alias => $class) {
            $types[$alias] = $alias;
            $types[$class] = $alias;
            $types['App\\Models\\'.class_basename($class)] = $alias;
        }

        return $types;
    }

    /**
     * @return array<string, string> hub table => morph alias of its model
     */
    private function entityTypes(): array
    {
        $types = [];

        foreach (MorphMap::MAP as $alias => $class) {
            $types[(new $class)->getTable()] = $alias;
        }

        return $types;
    }

    /**
     * Tables of the models that carry a public uuid (HasPublicUuid).
     *
     * @return list<string>
     */
    private function uuidTables(): array
    {
        $tables = [];

        foreach (MorphMap::MAP as $class) {
            if (in_array(HasPublicUuid::class, class_uses_recursive($class), true)) {
                $tables[] = (new $class)->getTable();
            }
        }

        return $tables;
    }

    /**
     * @return list<string>
     */
    private function primaryKey(string $table): array
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['primary']) {
                return $index['columns'];
            }
        }

        throw new RuntimeException("Hub table {$table} has no primary key.");
    }

    /**
     * Foreign keys pointing at this table itself or at a table loaded later:
     * written null first, filled in the second pass.
     *
     * @return list<string>
     */
    private function deferredColumns(string $table): array
    {
        $position = array_search($table, self::TABLES, true);
        $deferred = [];

        foreach (Schema::getForeignKeys($table) as $foreignKey) {
            $target = array_search($foreignKey['foreign_table'], self::TABLES, true);

            if ($foreignKey['columns'] === [self::ORDERED_SELF_REFERENCES[$table] ?? null]) {
                continue;
            }

            if ($target === false || $target >= $position) {
                array_push($deferred, ...$foreignKey['columns']);
            }
        }

        return array_values(array_unique($deferred));
    }

    private function sourceTable(string $table): string
    {
        return $this->source->getTablePrefix().$table;
    }

    /**
     * The frozen sub-program → parent mapping of migration 2026_10_30_200002.
     *
     * @return array<int, int>
     */
    private function frozenProgramParents(): array
    {
        $migration = require database_path('migrations/2026_10_30_200002_add_parent_id_to_programs_table.php');

        if (! is_object($migration) || ! method_exists($migration, 'parents')) {
            throw new RuntimeException('The programs parent migration no longer exposes parents().');
        }

        /** @var array<int, int> */
        return $migration::parents();
    }

    /**
     * The frozen old → new permission names of migration 2026_10_29_000002,
     * read from the migration itself so the two can never disagree.
     *
     * @return array<string, string>
     */
    private function frozenPermissionRenames(): array
    {
        $migration = require database_path('migrations/2026_10_29_000002_prefix_permission_names_by_module.php');

        if (! is_object($migration) || ! method_exists($migration, 'renames')) {
            throw new RuntimeException('The permission prefix migration no longer exposes renames().');
        }

        /** @var array<string, string> */
        return $migration::renames();
    }
}
