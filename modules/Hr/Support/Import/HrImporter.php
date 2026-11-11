<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Import;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Modules\Hr\Support\HrTables;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use stdClass;

/**
 * Copies the HR app's database into the hub (`php artisan hub:import-hr`).
 *
 * Re-runnable until launch, all inside one transaction with foreign key
 * checks off (HR has foreign keys that point both ways), checked for orphans
 * before it commits:
 *
 * 1. Tracking users were renumbered to their HR ids once (UserIdRemap; a
 *    no-op now that users.hr_user_id is gone).
 * 2. Core tables are upserted by HR id: cities (plus governorate 17
 *    "Al-Wadi", which tracking uses and HR lacks), centers, programs (HR
 *    columns only; tracking's own columns are kept) and users (HR wins the
 *    account columns; tracking keeps its locale, remember token and
 *    verification date; HR users without a row get one).
 * 3. Roles and permissions are matched by name and guard; HR's are added
 *    under hub ids. HR roles' permissions and members follow HR exactly,
 *    tracking roles are left alone, and HR's active-role flag fills
 *    users.active_role_id where it is still empty.
 * 4. Every HR-owned table (HrTables) is emptied and reloaded from HR.
 * 5. password_reset_tokens rows are upserted by email.
 *
 * Source tables that do not exist are reported as missing and skipped.
 */
final class HrImporter
{
    /**
     * Governorates the hub needs that HR does not have (user decision 2026-11-10).
     *
     * @var list<array<string, mixed>>
     */
    public const array EXTRA_CITIES = [
        ['id' => 17, 'name_en' => 'Al-Wadi', 'name_ar' => 'الوادي', 'slug' => 'WAD', 'code' => '016', 'hr_hotline' => null, 'is_active' => true],
    ];

    /**
     * HR stored full class names in its morph columns; the hub's enforced
     * morph map stores short keys.
     *
     * @var array<string, array<string, array<string, string>>>
     */
    public const array MORPH_TYPES = [
        'news' => ['newsable_type' => [
            'App\Models\City' => 'city',
            'App\Models\Center' => 'center',
            'App\Models\Program' => 'program',
        ]],
    ];

    private const int CHUNK = 200;

    /**
     * @var list<array{table: string, source: int|null, target: int, changed: int, note: string}>
     */
    private array $summary = [];

    /**
     * @var array{users: int, rows: array<string, int>}
     */
    private array $remap = ['users' => 0, 'rows' => []];

    private Connection $db;

    public function __construct(private readonly Connection $source)
    {
        $db = DB::connection();
        $this->db = $db;
    }

    public static function from(string $connection): self
    {
        $source = DB::connection($connection);

        return new self($source);
    }

    /**
     * Run the whole import.
     *
     * @return array{summary: list<array{table: string, source: int|null, target: int, changed: int, note: string}>, remap: array{users: int, rows: array<string, int>}}
     */
    public function run(): array
    {
        $this->summary = [];

        $this->db->transaction(function (): void {
            $schema = $this->db->getSchemaBuilder();
            $schema->disableForeignKeyConstraints();

            try {
                $this->remap = UserIdRemap::make()->run();

                $this->importCities();
                $this->importCenters();
                $this->importPrograms();
                $this->importUsers();
                $this->importAccess();

                foreach (HrTables::OWNED as $source => $target) {
                    $this->refresh($source, $target);
                }

                $this->importPasswordResetTokens();

                $orphans = OrphanCheck::make()->foreignKeys();

                if ($orphans !== []) {
                    throw new RuntimeException('Import left orphan rows, nothing was saved: '.json_encode($orphans));
                }
            } finally {
                $schema->enableForeignKeyConstraints();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ['summary' => $this->summary, 'remap' => $this->remap];
    }

    private function importCities(): void
    {
        $rows = $this->sourceRows('cities');

        if ($rows === null) {
            $this->missing('cities');

            return;
        }

        $known = array_map(fn (array $row): int => (int) $row['id'], $rows);

        foreach (self::EXTRA_CITIES as $city) {
            if (! in_array($city['id'], $known, true)) {
                $rows[] = $city + ['created_at' => null, 'updated_at' => null];
            }
        }

        $changed = $this->upsertById('cities', $rows, ['name_en', 'name_ar', 'slug', 'code', 'hr_hotline', 'is_active', 'created_at', 'updated_at']);
        $this->record('cities', count($known), $changed, 'Core, upserted by id (+ Al-Wadi id 17)');
    }

    private function importCenters(): void
    {
        $rows = $this->sourceRows('centers');

        if ($rows === null) {
            $this->missing('centers');

            return;
        }

        $changed = $this->upsertById('centers', $rows, ['name_en', 'name_ar', 'weekends', 'open_at', 'close_at', 'city_id', 'address_en', 'address_ar', 'is_active', 'created_at', 'updated_at']);
        $this->record('centers', count($rows), $changed, 'Core, upserted by id');
    }

    private function importPrograms(): void
    {
        $rows = $this->sourceRows('programs');

        if ($rows === null) {
            $this->missing('programs');

            return;
        }

        $changed = $this->upsertById('programs', $rows, ['name', 'description_en', 'description_ar', 'code', 'slug', 'start_date', 'end_date', 'is_active'], ['created_at', 'updated_at']);
        $this->record('programs', count($rows), $changed, 'Core, HR columns upserted by id');
    }

    /**
     * Upsert HR's accounts into users under their HR id.
     */
    private function importUsers(): void
    {
        $rows = $this->sourceRows('users');

        if ($rows === null) {
            $this->missing('users');

            return;
        }

        $existing = $this->db->table('users')->get()->keyBy('id');
        $changed = 0;

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            /** @var stdClass|null $current */
            $current = $existing->get($id);

            if ($current === null) {
                $this->db->table('users')->insert([
                    'id' => $id,
                    'employee_id' => $row['employee_id'] ?? null,
                    'email' => $row['email'],
                    'password' => $row['password'],
                    'email_verified_at' => $row['email_verified_at'] ?? null,
                    'locale' => $row['locale'] ?? null,
                    'remember_token' => $row['remember_token'] ?? null,
                    'last_seen_at' => $row['last_seen_at'] ?? null,
                    'created_at' => $row['created_at'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                ]);
                $changed++;

                continue;
            }

            $wanted = [
                'employee_id' => $row['employee_id'] ?? null,
                'email' => $row['email'],
                'password' => $row['password'],
                'last_seen_at' => $row['last_seen_at'] ?? null,
                'email_verified_at' => $current->email_verified_at ?? $row['email_verified_at'] ?? null,
                'locale' => $current->locale ?? $row['locale'] ?? null,
                'remember_token' => $current->remember_token ?? $row['remember_token'] ?? null,
            ];

            $diff = $this->differences((array) $current, $wanted);

            if ($diff !== []) {
                $this->db->table('users')->where('id', $id)->update($diff);
                $changed++;
            }
        }

        $this->record('users', count($rows), $changed, 'Core, upserted by HR id');
    }

    /**
     * Merge roles and permissions by name, then mirror HR's grants and members.
     */
    private function importAccess(): void
    {
        $roles = $this->sourceRows('roles');
        $permissions = $this->sourceRows('permissions');

        if ($roles === null || $permissions === null) {
            $this->missing('roles');
            $this->missing('permissions');

            return;
        }

        [$roleIds, $rolesAdded] = $this->matchByName('roles', $roles);
        [$permissionIds, $permissionsAdded] = $this->matchByName('permissions', $permissions);
        $this->record('roles', count($roles), $rolesAdded, 'shared, matched by name+guard');
        $this->record('permissions', count($permissions), $permissionsAdded, 'shared, matched by name+guard');

        $hrRoleIds = array_values($roleIds);

        $grants = [];
        foreach ($this->sourceRows('role_has_permissions') ?? [] as $row) {
            $roleId = $roleIds[(int) $row['role_id']] ?? null;
            $permissionId = $permissionIds[(int) $row['permission_id']] ?? null;

            if ($roleId !== null && $permissionId !== null) {
                $grants["{$roleId}:{$permissionId}"] = ['role_id' => $roleId, 'permission_id' => $permissionId];
            }
        }
        $changed = $this->mirrorPivot('role_has_permissions', 'role_id', $hrRoleIds, $grants, fn (stdClass $row): string => "{$row->role_id}:{$row->permission_id}");
        $this->record('role_has_permissions', count($this->sourceRows('role_has_permissions') ?? []), $changed, 'HR roles mirror HR');

        $userIds = array_flip($this->db->table('users')->pluck('id')->map(fn (mixed $id): int => (int) $id)->all());
        $members = [];
        $active = [];
        $orphans = 0;
        $memberRows = $this->sourceRows('model_has_roles') ?? [];

        foreach ($memberRows as $row) {
            $roleId = $roleIds[(int) $row['role_id']] ?? null;
            $userId = (int) $row['model_id'];

            if ($roleId === null || ! isset($userIds[$userId])) {
                $orphans++;

                continue;
            }

            $members["{$roleId}:{$userId}"] = ['role_id' => $roleId, 'model_type' => 'user', 'model_id' => $userId];

            if ((bool) ($row['is_active'] ?? false)) {
                $active[$userId] ??= $roleId;
            }
        }
        $changed = $this->mirrorPivot('model_has_roles', 'role_id', $hrRoleIds, $members, fn (stdClass $row): string => "{$row->role_id}:{$row->model_id}");

        foreach ($active as $userId => $roleId) {
            $changed += $this->db->table('users')->where('id', $userId)->whereNull('active_role_id')->update(['active_role_id' => $roleId]);
        }
        $this->record('model_has_roles', count($memberRows), $changed, "HR roles mirror HR, morph 'user'".($orphans > 0 ? ", {$orphans} orphan skipped" : ''));

        $direct = 0;
        foreach ($this->sourceRows('model_has_permissions') ?? [] as $row) {
            $permissionId = $permissionIds[(int) $row['permission_id']] ?? null;

            if ($permissionId !== null && isset($userIds[(int) $row['model_id']])) {
                $direct += $this->db->table('model_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'model_type' => 'user', 'model_id' => (int) $row['model_id']]);
            }
        }
        $this->record('model_has_permissions', count($this->sourceRows('model_has_permissions') ?? []), $direct, 'added only');
    }

    /**
     * Find or add each HR row by name and guard.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array{0: array<int, int>, 1: int} HR id => hub id, rows added
     */
    private function matchByName(string $table, array $rows): array
    {
        $map = [];
        $added = 0;

        foreach ($rows as $row) {
            $id = $this->db->table($table)->where('name', $row['name'])->where('guard_name', $row['guard_name'])->value('id');

            if ($id === null) {
                $id = $this->db->table($table)->insertGetId([
                    'name' => $row['name'],
                    'guard_name' => $row['guard_name'],
                    'created_at' => $row['created_at'] ?? null,
                    'updated_at' => $row['updated_at'] ?? null,
                ]);
                $added++;
            }

            $map[(int) $row['id']] = (int) $id;
        }

        return [$map, $added];
    }

    /**
     * Make the pivot rows of the given roles exactly $wanted.
     *
     * @param  list<int>  $roleIds
     * @param  array<string, array<string, mixed>>  $wanted
     * @param  callable(stdClass): string  $key
     */
    private function mirrorPivot(string $table, string $roleColumn, array $roleIds, array $wanted, callable $key): int
    {
        $changed = 0;
        $current = $this->db->table($table)->whereIn($roleColumn, $roleIds)->get();

        foreach ($current as $row) {
            $rowKey = $key($row);

            if (isset($wanted[$rowKey])) {
                unset($wanted[$rowKey]);

                continue;
            }

            $this->db->table($table)->where((array) $row)->delete();
            $changed++;
        }

        foreach (array_chunk(array_values($wanted), self::CHUNK) as $chunk) {
            $this->db->table($table)->insert($chunk);
            $changed += count($chunk);
        }

        return $changed;
    }

    /**
     * Empty one HR-owned table and reload it from HR.
     */
    private function refresh(string $source, string $target): void
    {
        $rows = $this->sourceRows($source);

        if ($rows === null) {
            $this->missing($target);

            return;
        }

        $before = $this->fingerprint($target);
        $columns = array_flip($this->db->getSchemaBuilder()->getColumnListing($target));

        $this->db->table($target)->delete();

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            $this->db->table($target)->insert(array_map(fn (array $row): array => $this->morphKeys($target, array_intersect_key($row, $columns)), $chunk));
        }

        $this->record($target, count($rows), $before === $this->fingerprint($target) ? 0 : count($rows), $source === $target ? 'HR-owned, reloaded' : "HR-owned, reloaded from {$source}");
    }

    /**
     * Swap HR's class names for the hub's morph keys (see MORPH_TYPES).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function morphKeys(string $table, array $row): array
    {
        foreach (self::MORPH_TYPES[$table] ?? [] as $column => $keys) {
            if (is_string($row[$column] ?? null) && isset($keys[$row[$column]])) {
                $row[$column] = $keys[$row[$column]];
            }
        }

        return $row;
    }

    private function importPasswordResetTokens(): void
    {
        $rows = $this->sourceRows('password_reset_tokens');

        if ($rows === null) {
            $this->missing('password_reset_tokens');

            return;
        }

        $changed = 0;
        foreach ($rows as $row) {
            $current = $this->db->table('password_reset_tokens')->where('email', $row['email'])->first();

            if ($current === null || $this->differences((array) $current, $row) !== []) {
                $this->db->table('password_reset_tokens')->updateOrInsert(['email' => $row['email']], ['token' => $row['token'], 'created_at' => $row['created_at'] ?? null]);
                $changed++;
            }
        }

        $this->record('password_reset_tokens', count($rows), $changed, 'shared, upserted by email');
    }

    /**
     * Insert new rows and update changed ones, by id.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $columns  columns HR owns, kept in step
     * @param  list<string>  $insertOnly  columns written only when the row is new
     */
    private function upsertById(string $table, array $rows, array $columns, array $insertOnly = []): int
    {
        $existing = $this->db->table($table)->get()->keyBy('id');
        $changed = 0;

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $wanted = array_intersect_key($row, array_flip($columns));
            /** @var stdClass|null $current */
            $current = $existing->get($id);

            if ($current === null) {
                $this->db->table($table)->insert(['id' => $id] + $wanted + array_intersect_key($row, array_flip($insertOnly)));
                $changed++;

                continue;
            }

            $diff = $this->differences((array) $current, $wanted);

            if ($diff !== []) {
                $this->db->table($table)->where('id', $id)->update($diff);
                $changed++;
            }
        }

        return $changed;
    }

    /**
     * The wanted values that differ from the stored ones, compared as strings
     * (booleans as 0/1) so driver types do not count as changes.
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $wanted
     * @return array<string, mixed>
     */
    private function differences(array $current, array $wanted): array
    {
        $normalise = fn (mixed $value): ?string => match (true) {
            $value === null => null,
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };

        return array_filter(
            $wanted,
            fn (mixed $value, string $column): bool => $normalise($current[$column] ?? null) !== $normalise($value),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Every row of a source table as arrays, or null when HR has no such table.
     *
     * @return list<array<string, mixed>>|null
     */
    private function sourceRows(string $table): ?array
    {
        if (! $this->source->getSchemaBuilder()->hasTable($table)) {
            return null;
        }

        return array_values(array_map(
            fn (mixed $row): array => (array) $row,
            $this->source->table($table)->get()->all(),
        ));
    }

    /**
     * A checksum of a table's content, to tell whether a reload changed it.
     */
    private function fingerprint(string $table): string
    {
        return md5((string) json_encode($this->db->table($table)->get()->map(fn (stdClass $row): array => array_map(
            fn (mixed $value): ?string => $value === null ? null : (string) $value,
            (array) $row,
        ))->sortBy(fn (array $row): string => (string) json_encode($row))->values()->all()));
    }

    private function missing(string $table): void
    {
        $this->record($table, null, 0, 'source table missing, skipped');
    }

    private function record(string $table, ?int $source, int $changed, string $note): void
    {
        $this->summary[] = [
            'table' => $table,
            'source' => $source,
            'target' => $this->db->getSchemaBuilder()->hasTable($table) ? $this->db->table($table)->count() : 0,
            'changed' => $changed,
            'note' => $note,
        ];
    }
}
