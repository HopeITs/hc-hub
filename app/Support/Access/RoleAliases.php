<?php

declare(strict_types=1);

namespace App\Support\Access;

use App\Models\Role;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Which roles a role name stands for (roadmap 4.2, D5). HR asks "is this the
 * general manager?" by name, and two roles carry that meaning: HR's
 * general-manager and the hub's General Manager. The pairs are rows in
 * role_aliases (alias, role_id), so a renamed role keeps its meaning.
 *
 * config('access-control.role_aliases') names the pairs: a role created under
 * one of those names is linked at once, `permissions:sync` links the ones
 * that are missing, and an alias with no row yet falls back to the configured
 * names. The table is temporary: it goes once every check reads a permission.
 */
final class RoleAliases
{
    /** @var array<string, list<string>>|null */
    private ?array $linked = null;

    /**
     * The current names, in upper case, of the roles the alias stands for.
     *
     * @return list<string>
     */
    public function roleNames(string $alias): array
    {
        $alias = self::key($alias);
        $this->linked ??= $this->load();

        if (isset($this->linked[$alias])) {
            return $this->linked[$alias];
        }

        return array_map(
            fn (string $name): string => strtoupper(trim($name)),
            self::configured()[$alias] ?? [],
        );
    }

    /**
     * Link a role to the aliases its name is configured under.
     */
    public function link(Role $role): void
    {
        foreach (self::configured() as $alias => $names) {
            if (in_array($role->name, $names, true)) {
                try {
                    DB::table('role_aliases')->insertOrIgnore([
                        'alias' => $alias,
                        'role_id' => $role->getKey(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (QueryException) {
                    // An older migration creating a role before the table exists; its own migration links it.
                }
            }
        }

        $this->forget();
    }

    /**
     * The configured pairs that have a role but no row yet.
     *
     * @return list<array{alias: string, role_id: int, role: string}>
     */
    public function missing(): array
    {
        $roles = Role::query()->where('guard_name', 'web')->pluck('id', 'name');
        $existing = DB::table('role_aliases')->get()->map(fn (object $row): string => "{$row->alias}:{$row->role_id}")->all();
        $missing = [];

        foreach (self::configured() as $alias => $names) {
            foreach ($names as $name) {
                $roleId = $roles->get($name);

                if ($roleId !== null && ! in_array("{$alias}:{$roleId}", $existing, true)) {
                    $missing[] = ['alias' => $alias, 'role_id' => (int) $roleId, 'role' => $name];
                }
            }
        }

        return $missing;
    }

    public function forget(): void
    {
        $this->linked = null;
    }

    /**
     * @return array<string, list<string>>
     */
    private function load(): array
    {
        try {
            $rows = DB::table('role_aliases')
                ->join('roles', 'roles.id', '=', 'role_aliases.role_id')
                ->get(['role_aliases.alias', 'roles.name']);
        } catch (QueryException) {
            // Before the migration has run: the configured names decide.
            return [];
        }

        $linked = [];

        foreach ($rows as $row) {
            $linked[(string) $row->alias][] = strtoupper(trim((string) $row->name));
        }

        return $linked;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function configured(): array
    {
        /** @var array<string, list<string>> $aliases */
        $aliases = config('access-control.role_aliases', []);

        return $aliases;
    }

    private static function key(string $alias): string
    {
        return str_replace(' ', '-', strtolower(trim($alias)));
    }
}
