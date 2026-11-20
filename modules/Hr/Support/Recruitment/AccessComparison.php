<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Models\City;

/**
 * What each account may do in recruitment today, and what it would do once
 * recruitment adds up the permissions of every role a person holds, like the
 * rest of the hub (roadmap 4.5, decision D5). Read only: it changes nothing
 * and nothing reads it to decide access.
 *
 * Today (Modules\Hr\Support\Recruitment\Access): only the role a person is
 * acting under counts (users.active_role_id, else their first role), plus
 * what was granted to them directly. An active super-admin, HR-SYR or hub
 * administrator role passes every check. The governorate limit and reading
 * pay follow that same single role.
 *
 * After: every role the person holds counts. They pass every check when any
 * of their roles is an administrator one; they are HR when any role is
 * HR-<CITY>; their governorates are those of all their HR roles (no limit
 * when one of them is national or they pass every check); and they read pay when they are HR and hold no
 * role that is blind to pay. That last rule is a proposal: the owner decides.
 */
final class AccessComparison
{
    /** Old HR role names that pass every permission check (Access::HR_ADMINISTRATORS). */
    private const array HR_ADMINISTRATORS = ['super-admin', 'HR-SYR'];

    /**
     * One row per account that holds a role or a direct permission.
     *
     * @return list<array{
     *     user_id: int, name: string, email: string, employee_id: int|null, roles: list<string>,
     *     active_role: string|null,
     *     before: array{administrator: bool, hr: bool, cities: string, salary: bool, permissions: list<string>},
     *     after: array{administrator: bool, hr: bool, cities: string, salary: bool, permissions: list<string>},
     *     gained: list<string>, lost: list<string>, changes: list<string>
     * }>
     */
    public function rows(): array
    {
        $tables = config('permission.table_names');
        $catalog = $this->recruitmentPermissions();
        $roleNames = DB::table($tables['roles'])->pluck('name', 'id')->map(fn (mixed $name): string => (string) $name)->all();

        $rolePermissions = [];

        foreach (DB::table($tables['role_has_permissions'].' as grants')
            ->join($tables['permissions'].' as permissions', 'permissions.id', '=', 'grants.permission_id')
            ->where('permissions.name', 'like', 'recruitment-%')
            ->get(['grants.role_id', 'permissions.name']) as $row) {
            $rolePermissions[(int) $row->role_id][] = (string) $row->name;
        }

        $userRoles = [];

        foreach (DB::table($tables['model_has_roles'])->where('model_type', 'user')->orderBy('role_id')->get(['model_id', 'role_id']) as $row) {
            $userRoles[(int) $row->model_id][] = (int) $row->role_id;
        }

        $direct = [];

        foreach (DB::table($tables['model_has_permissions'].' as grants')
            ->join($tables['permissions'].' as permissions', 'permissions.id', '=', 'grants.permission_id')
            ->where('grants.model_type', 'user')
            ->where('permissions.name', 'like', 'recruitment-%')
            ->get(['grants.model_id', 'permissions.name']) as $row) {
            $direct[(int) $row->model_id][] = (string) $row->name;
        }

        $userIds = array_unique([...array_keys($userRoles), ...array_keys($direct)]);
        $rows = [];

        foreach (DB::table('users')
            ->leftJoin('employees', 'employees.id', '=', 'users.employee_id')
            ->whereIn('users.id', $userIds)
            ->orderBy('users.id')
            ->get(['users.id', 'users.email', 'users.employee_id', 'users.active_role_id', 'employees.first_name_en', 'employees.last_name_en']) as $user) {
            $userId = (int) $user->id;
            $heldIds = $userRoles[$userId] ?? [];
            $held = array_map(fn (int $id): string => $roleNames[$id] ?? '?', $heldIds);
            $own = $direct[$userId] ?? [];

            $activeId = $user->active_role_id !== null && isset($roleNames[(int) $user->active_role_id])
                ? (int) $user->active_role_id
                : ($heldIds[0] ?? null);
            $active = $activeId === null ? null : $roleNames[$activeId];

            $before = $this->standing($active === null ? [] : [$active], [...$own, ...($activeId === null ? [] : ($rolePermissions[$activeId] ?? []))], $catalog);
            $after = $this->standing($held, [...$own, ...array_merge(...array_map(fn (int $id): array => $rolePermissions[$id] ?? [], $heldIds), ...[[]])], $catalog);

            $gained = array_values(array_diff($after['permissions'], $before['permissions']));
            $lost = array_values(array_diff($before['permissions'], $after['permissions']));
            $changes = [];

            foreach (['administrator', 'hr', 'cities', 'salary'] as $key) {
                if ($before[$key] !== $after[$key]) {
                    $changes[] = $key;
                }
            }

            $rows[] = [
                'user_id' => $userId,
                'name' => trim(((string) $user->first_name_en).' '.((string) $user->last_name_en)),
                'email' => (string) $user->email,
                'employee_id' => $user->employee_id === null ? null : (int) $user->employee_id,
                'roles' => $held,
                'active_role' => $active,
                'before' => $before,
                'after' => $after,
                'gained' => $gained,
                'lost' => $lost,
                'changes' => $changes,
            ];
        }

        return $rows;
    }

    /**
     * Every recruitment permission in the database.
     *
     * @return list<string>
     */
    public function recruitmentPermissions(): array
    {
        return array_values(DB::table(config('permission.table_names.permissions'))
            ->where('name', 'like', 'recruitment-%')
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (mixed $name): string => (string) $name)
            ->all());
    }

    /**
     * What a set of counted roles and granted permissions amounts to.
     *
     * @param  list<string>  $roles
     * @param  list<string>  $granted
     * @param  list<string>  $catalog
     * @return array{administrator: bool, hr: bool, cities: string, salary: bool, permissions: list<string>}
     */
    private function standing(array $roles, array $granted, array $catalog): array
    {
        $normal = array_map(fn (string $role): string => str_replace(' ', '-', strtoupper(trim($role))), $roles);
        $administrators = array_map(
            fn (string $role): string => str_replace(' ', '-', strtoupper(trim($role))),
            [...self::HR_ADMINISTRATORS, ...(array) config('access-control.administrator_roles', [])],
        );
        $blind = array_map(
            fn (mixed $role): string => str_replace(' ', '-', strtoupper(trim((string) $role))),
            (array) config('salary.blind_roles', []),
        );

        $administrator = array_intersect($normal, $administrators) !== [];
        $hrRoles = array_values(array_filter($normal, fn (string $role): bool => Str::startsWith($role, 'HR-')));
        $hr = $hrRoles !== [];
        $national = in_array('HR-'.City::NATIONAL_SLUG, $hrRoles, true);

        $cities = 'all';

        if ($hr && ! $national && ! $administrator) {
            $slugs = [];

            foreach ($hrRoles as $role) {
                foreach (City::slugsForHrRole($role) as $slug) {
                    $slugs[] = strtoupper(trim($slug));
                }
            }

            $slugs = array_values(array_unique($slugs));
            sort($slugs);
            $cities = implode(' ', $slugs);
        }

        $permissions = $administrator ? $catalog : array_values(array_unique($granted));
        sort($permissions);

        return [
            'administrator' => $administrator,
            'hr' => $hr,
            'cities' => $cities,
            'salary' => $hr && array_intersect($normal, $blind) === [],
            'permissions' => $permissions,
        ];
    }
}
