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
 * What the old HR app asked its User model: the role a person is acting
 * under (users.active_role_id), the permissions of that role, and the
 * governorate an HR-<CITY> role is limited to. Recruitment rules are written
 * against the active role, so they are answered here and not by the additive
 * can() of Spatie, which would let the other roles of a person leak in.
 */
final class Access
{
    /** Old HR role names that pass every permission check. */
    private const HR_ADMINISTRATORS = ['super-admin', 'HR-SYR'];

    /** Roles that may never read pay (old config salary.blind_roles). */
    private const SALARY_BLIND = ['super-admin'];

    /** @var array<int, array{role: ?string, permissions: array<string, true>}> */
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

    public static function activeRoleIs(?User $user, string ...$roles): bool
    {
        $active = self::normalize(self::myRole($user));

        foreach ($roles as $role) {
            $role = self::normalize($role);

            if ($role === 'HR' && Str::startsWith($active, 'HR-')) {
                return true;
            }

            if ($active !== '' && $active === $role) {
                return true;
            }
        }

        return false;
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
        $role = strtoupper(trim((string) self::myRole($user)));

        if (! Str::startsWith($role, 'HR-') || $role === 'HR-SYR') {
            return null;
        }

        $ids = City::idsBySlug();
        $found = [];

        foreach (City::slugsForHrRole($role) as $slug) {
            $slug = strtoupper(trim($slug));

            if (isset($ids[$slug]) && $ids[$slug] > 0) {
                $found[] = $ids[$slug];
            }
        }

        return $found;
    }

    /** The home governorate of the role (the one named after "HR-"), null when it sees all. */
    public static function activeCityId(?User $user): ?int
    {
        $role = strtoupper(trim((string) self::myRole($user)));

        if (! Str::startsWith($role, 'HR-') || $role === 'HR-SYR') {
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
     * @return array{role: ?string, permissions: array<string, true>}
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

        $viaRole = $roleId === null ? collect() : DB::table($tables['role_has_permissions'].' as grants')
            ->join($tables['permissions'].' as permissions', 'permissions.id', '=', 'grants.permission_id')
            ->where('grants.role_id', $roleId)
            ->pluck('permissions.name');

        $permissions = [];

        foreach ($direct->merge($viaRole) as $name) {
            $permissions[(string) $name] = true;
        }

        return self::$memo[$memoKey] = ['role' => $role === null ? null : (string) $role, 'permissions' => $permissions];
    }

    private static function normalize(?string $role): string
    {
        return str_replace(' ', '-', strtoupper(trim((string) $role)));
    }
}
