<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Employees;

use App\Models\Role;
use Illuminate\Support\Str;
use Modules\Core\Models\City;
use Modules\Core\Models\User;

/**
 * Who a user is to HR: their active role (users.active_role_id), the cities a
 * regional HR role (HR-ALP) is limited to, and whether they may read pay.
 *
 * The old HR app asked the User model (activeRoleIs, activeCityIds,
 * canAccessCity, canViewSalary); the hub keeps those rules here so Core's
 * User stays free of HR.
 */
final class HrAccess
{
    /** HR's role names beside the hub roles that mean the same thing. */
    private const ALIASES = [
        'SUPER-ADMIN' => ['SUPER-ADMIN', 'SUPER ADMIN'],
        'GENERAL-MANAGER' => ['GENERAL-MANAGER', 'GENERAL MANAGER'],
        'MANAGER' => ['MANAGER', 'MANAGERS'],
        'EMPLOYEE' => ['EMPLOYEE', 'TEAM MEMBER'],
    ];

    /** The role a user without an active role is read as, most senior first. */
    private const FALLBACK_ORDER = ['SUPER-ADMIN', 'HR', 'GENERAL-MANAGER', 'MANAGER'];

    /**
     * The active role name in upper case: users.active_role_id when set, else
     * the most senior role the user holds.
     */
    public static function roleName(?User $user): string
    {
        if ($user === null) {
            return '';
        }

        return once(function () use ($user): string {
            if ($user->active_role_id !== null) {
                $name = Role::query()->whereKey($user->active_role_id)->value('name');

                if (is_string($name) && $name !== '') {
                    return strtoupper(trim($name));
                }
            }

            $names = $user->roles()->pluck('name')
                ->map(fn (mixed $name): string => strtoupper(trim((string) $name)));

            foreach (self::FALLBACK_ORDER as $wanted) {
                $match = $names->first(fn (string $name): bool => self::matches($name, $wanted));

                if ($match !== null) {
                    return $match;
                }
            }

            return (string) ($names->first() ?? '');
        });
    }

    /**
     * Whether the active role is one of the given roles. "HR" matches every
     * HR-<CITY> role.
     */
    public static function is(?User $user, string ...$roles): bool
    {
        $active = self::roleName($user);

        if ($active === '') {
            return false;
        }

        foreach ($roles as $role) {
            if (self::matches($active, strtoupper(trim($role)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * The city ids a regional HR role is limited to; null means no limit
     * (HR-SYR and every role that is not HR).
     *
     * @return list<int>|null
     */
    public static function cityIds(?User $user): ?array
    {
        $role = self::roleName($user);

        if (! Str::startsWith($role, 'HR-') || $role === 'HR-'.City::NATIONAL_SLUG) {
            return null;
        }

        $bySlug = City::idsBySlug();

        return array_values(array_filter(array_map(
            fn (string $slug): ?int => $bySlug[strtoupper($slug)] ?? null,
            City::slugsForHrRole($role),
        ), fn (?int $id): bool => $id !== null && $id !== 0));
    }

    public static function canAccessCity(?User $user, ?int $cityId): bool
    {
        $cityIds = self::cityIds($user);

        return $cityIds === null || in_array((int) $cityId, $cityIds, true);
    }

    /**
     * Pay is read by HR only; the roles in config('salary.blind_roles')
     * (the super admin) never see it.
     */
    public static function canViewSalary(?User $user): bool
    {
        if ($user === null || self::is($user, ...config('salary.blind_roles', []))) {
            return false;
        }

        return self::is($user, 'HR');
    }

    private static function matches(string $active, string $wanted): bool
    {
        if ($wanted === 'HR') {
            return Str::startsWith($active, 'HR-');
        }

        return $active === $wanted || in_array($active, self::ALIASES[$wanted] ?? [], true);
    }
}
