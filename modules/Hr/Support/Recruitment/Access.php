<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;
use Modules\Core\Models\City;
use Modules\Core\Models\User;

/**
 * What recruitment asks about a person: which roles count, what they may do
 * and the governorates an HR-<CITY> role is limited to.
 *
 * Every role a person holds counts, as in the rest of the hub (roadmap 4.5,
 * decision D5, approved by the owner on 2026-11-20 after the before/after
 * table of hub:recruitment-access): the permissions of all their roles add
 * up, any administrator role passes every check, the governorates of all
 * their HR roles apply, and a role that is blind to pay hides it whatever
 * else they hold. config('access-control.recruitment_roles') = 'active'
 * brings back the old HR rule, where only the role a person is acting under
 * (users.active_role_id, else their first role) counted.
 */
final class Access
{
    /** Old HR role names that pass every permission check. */
    private const HR_ADMINISTRATORS = ['super-admin', 'HR-SYR'];

    /** Roles that may never read pay (old config salary.blind_roles). */
    private const SALARY_BLIND = ['super-admin'];

    /** @var array<int, array{role: ?string, roles: list<string>, permissions: array<string, true>}> */
    private static array $memo = [];

    /** @var array<string, mixed> per-user values for the length of one request (the old User::remember) */
    private static array $remembered = [];

    public static function flush(): void
    {
        self::$memo = [];
        self::$remembered = [];
    }

    public static function remember(User $user, string $key, \Closure $resolve): mixed
    {
        $key = $user->getKey().'|'.($user->getAttributes()['active_role_id'] ?? '').'|'.$key;

        if (! array_key_exists($key, self::$remembered)) {
            self::$remembered[$key] = $resolve();
        }

        return self::$remembered[$key];
    }

    public static function myRole(?User $user): ?string
    {
        return $user === null ? null : self::state($user)['role'];
    }

    /**
     * Whether one of the roles that count for this person is one of the
     * given roles. "HR" matches every HR-<CITY> role.
     */
    public static function activeRoleIs(?User $user, string ...$roles): bool
    {
        $counted = self::countedRoles($user);

        foreach ($roles as $role) {
            $role = self::normalize($role);

            foreach ($counted as $held) {
                if ($held === $role || ($role === 'HR' && Str::startsWith($held, 'HR-'))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The roles that count for this person, in upper case with dashes: all
     * they hold, or only the one they act under in 'active' mode.
     *
     * @return list<string>
     */
    public static function countedRoles(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return array_values(array_filter(array_map(self::normalize(...), self::state($user)['roles'])));
    }

    /**
     * The regional HR roles (HR-ALP, never the national HR-SYR) that count
     * for this person; empty when one of their roles sees every governorate.
     *
     * @return list<string>
     */
    public static function regionalHrRoles(?User $user): array
    {
        $counted = self::countedRoles($user);

        if (in_array('HR-SYR', $counted, true) || self::isAdministrator($user)) {
            return [];
        }

        return array_values(array_filter($counted, fn (string $role): bool => Str::startsWith($role, 'HR-')));
    }

    public static function everyRoleCounts(): bool
    {
        return config('access-control.recruitment_roles', 'all') !== 'active';
    }

    /** Acting under any HR role: national (HR-SYR) or regional (HR-ALP). */
    public static function isHr(?User $user): bool
    {
        return self::activeRoleIs($user, 'HR');
    }

    public static function isActiveSuperAdmin(?User $user): bool
    {
        return self::activeRoleIs($user, 'super-admin');
    }

    public static function isAdministrator(?User $user): bool
    {
        return self::activeRoleIs($user, ...self::HR_ADMINISTRATORS, ...(array) config('access-control.administrator_roles', []));
    }

    public static function hasActivePermission(?User $user, string $permission): bool
    {
        return $user !== null && (self::isAdministrator($user) || self::hasExplicitPermission($user, $permission));
    }

    /**
     * A direct grant or a permission of the active role, with no
     * administrator bypass (candidate export and erasure).
     */
    public static function hasExplicitPermission(?User $user, string $permission): bool
    {
        return $user !== null && isset(self::state($user)['permissions'][$permission]);
    }

    public static function canViewSalary(?User $user): bool
    {
        if ($user === null || self::activeRoleIs($user, ...self::SALARY_BLIND)) {
            return false;
        }

        return self::activeRoleIs($user, 'HR');
    }

    /**
     * The cities the active role is limited to, or null when it sees all.
     *
     * @return list<int>|null
     */
    public static function activeCityIds(?User $user): ?array
    {
        $roles = self::regionalHrRoles($user);

        if ($roles === []) {
            return null;
        }

        $ids = City::idsBySlug();
        $found = [];

        foreach ($roles as $role) {
            foreach (City::slugsForHrRole($role) as $slug) {
                $slug = strtoupper(trim($slug));

                if (isset($ids[$slug]) && $ids[$slug] > 0) {
                    $found[] = $ids[$slug];
                }
            }
        }

        return array_values(array_unique($found));
    }

    /** The home governorate of the role (the one named after "HR-"), null when it sees all. */
    public static function activeCityId(?User $user): ?int
    {
        $role = self::regionalHrRoles($user)[0] ?? null;

        if ($role === null) {
            return null;
        }

        $id = City::idsBySlug()[Str::after($role, 'HR-')] ?? null;

        return $id === null || $id === 0 ? null : $id;
    }

    public static function canAccessCity(?User $user, ?int $cityId): bool
    {
        $cityIds = self::activeCityIds($user);

        return $cityIds === null || in_array((int) $cityId, $cityIds, true);
    }

    /**
     * Accounts of active employees, optionally of one governorate: who an
     * organisation-wide notice goes to.
     *
     * @return Builder<User>
     */
    public static function broadcastRecipientsQuery(?int $cityId = null): Builder
    {
        return User::query()
            ->whereNotNull('employee_id')
            ->whereHas('employee', function (Builder $query) use ($cityId): void {
                $query->where('is_active', true);

                if ($cityId !== null) {
                    $query->where('city_id', $cityId);
                }
            });
    }

    public static function sendBroadcast(Notification $notification, ?int $cityId = null): void
    {
        self::broadcastRecipientsQuery($cityId)->chunkById(100, function ($users) use ($notification): void {
            NotificationFacade::send($users, $notification);
        });
    }

    /**
     * The role named in logs (the one acted under, else the first held), the
     * roles that count, and the permissions they and direct grants give.
     *
     * @return array{role: ?string, roles: list<string>, permissions: array<string, true>}
     */
    private static function state(User $user): array
    {
        $key = (int) $user->getKey();
        $roleId = $user->getAttributes()['active_role_id'] ?? null;
        $memoKey = $key * 100000 + (int) $roleId;

        if (isset(self::$memo[$memoKey])) {
            return self::$memo[$memoKey];
        }

        $tables = config('permission.table_names');
        $role = $roleId === null ? null : Role::query()->whereKey($roleId)->value('name');

        if ($role === null) {
            $roleId = DB::table($tables['model_has_roles'])
                ->where('model_id', $key)
                ->where('model_type', $user->getMorphClass())
                ->orderBy('role_id')
                ->value('role_id');
            $role = $roleId === null ? null : Role::query()->whereKey($roleId)->value('name');
        }

        $direct = DB::table($tables['model_has_permissions'].' as grants')
            ->join($tables['permissions'].' as permissions', 'permissions.id', '=', 'grants.permission_id')
            ->where('grants.model_id', $key)
            ->where('grants.model_type', $user->getMorphClass())
            ->pluck('permissions.name');

        $countedIds = $roleId === null ? [] : [(int) $roleId];
        $countedNames = $role === null ? [] : [(string) $role];

        if (self::everyRoleCounts()) {
            $held = DB::table($tables['model_has_roles'].' as members')
                ->join($tables['roles'].' as roles', 'roles.id', '=', 'members.role_id')
                ->where('members.model_id', $key)
                ->where('members.model_type', $user->getMorphClass())
                ->orderBy('roles.id')
                ->pluck('roles.name', 'roles.id');

            $countedIds = array_map(fn (mixed $id): int => (int) $id, $held->keys()->all());
            $countedNames = array_map(fn (mixed $name): string => (string) $name, $held->values()->all());
        }

        $viaRole = $countedIds === [] ? collect() : DB::table($tables['role_has_permissions'].' as grants')
            ->join($tables['permissions'].' as permissions', 'permissions.id', '=', 'grants.permission_id')
            ->whereIn('grants.role_id', $countedIds)
            ->pluck('permissions.name');

        $permissions = [];

        foreach ($direct->merge($viaRole) as $name) {
            $permissions[(string) $name] = true;
        }

        return self::$memo[$memoKey] = ['role' => $role === null ? null : (string) $role, 'roles' => $countedNames, 'permissions' => $permissions];
    }

    private static function normalize(?string $role): string
    {
        return str_replace(' ', '-', strtoupper(trim((string) $role)));
    }
}
