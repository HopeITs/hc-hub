<?php

namespace Tests\Concerns;

use App\Models\City;
use App\Models\Employee;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Fixtures for the city-scoping and authorization tests.
 *
 * The scoping rules depend on the exact shape of a role name (HR-ALP resolves
 * to a city, HR-SYR and super-admin deliberately do not), so these helpers
 * build users the same way the seeder does rather than stubbing activeCityIds().
 */
trait CreatesHrFixtures
{
    protected function makeCity(string $slug, string $code): City
    {
        return City::query()->forceCreate([
            'name_en' => $slug,
            'name_ar' => $slug,
            'slug' => $slug,
            'code' => $code,
            'is_active' => true,
        ]);
    }

    protected function makeEmployee(City $city, string $username, array $attributes = []): Employee
    {
        return Employee::query()->create(array_merge([
            'city_id' => $city->id,
            'first_name_en' => 'Test',
            'first_name_ar' => 'اختبار',
            'last_name_en' => 'Person',
            'last_name_ar' => 'شخص',
            'username' => $username,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * A user whose *active* role is $roleName, holding $permissions.
     *
     * The active-role pivot matters: several of the bugs under test came from
     * code that inspected every assigned role instead of the active one.
     */
    protected function makeUser(City $city, string $roleName, array $permissions = [], ?Employee $employee = null): User
    {
        $employee ??= $this->makeEmployee($city, 'user-'.$roleName.'-'.uniqid());
        $user = User::factory()->create(['employee_id' => $employee->id]);

        $role = Role::findOrCreate($roleName, 'web');

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $user->assignRole($role);
        $user->roles()->updateExistingPivot($role->id, ['is_active' => true]);
        $user->unsetRelation('roles');

        return $user->fresh();
    }

    /** Assign an extra role that is explicitly *not* active. */
    protected function assignInactiveRole(User $user, string $roleName, array $permissions = []): User
    {
        $role = Role::findOrCreate($roleName, 'web');

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $user->assignRole($role);
        $user->roles()->updateExistingPivot($role->id, ['is_active' => false]);
        $user->unsetRelation('roles');

        return $user->fresh();
    }
}
