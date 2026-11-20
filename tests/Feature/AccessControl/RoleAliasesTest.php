<?php

use App\Models\Role;
use App\Support\Access\RoleAliases;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Hr\Models\Employee;

/*
| Roadmap 4.2: HR asks "is this the general manager?" by role name, and two
| names mean the same thing (HR's general-manager, the hub's General Manager).
| The pairs used to be a constant in HrAccess; they are rows in role_aliases
| now, so renaming a role no longer breaks the check silently (R9).
*/

/**
 * @return array<string, list<string>>
 */
function rolePermissionMatrix(): array
{
    return DB::table('role_has_permissions')
        ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
        ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
        ->orderBy('roles.name')
        ->orderBy('permissions.name')
        ->get(['roles.name as role', 'permissions.name as permission'])
        ->groupBy('role')
        ->map(fn ($rows): array => $rows->pluck('permission')->all())
        ->all();
}

test('each role reads a colleague\'s records and manages ID cards as before', function (string $role, bool $readsRecords, bool $managesCard) {
    $user = userWithRole($role, Employee::factory()->create()->id);
    $colleague = Employee::factory()->create(['is_active' => true]);

    expect($user->can('viewRecords', $colleague))->toBe($readsRecords)
        ->and($user->can('manageCard', $colleague))->toBe($managesCard);
})->with([
    'super-admin' => ['super-admin', true, true],
    'Super Admin' => ['Super Admin', true, true],
    'HR-SYR' => ['HR-SYR', true, true],
    'general-manager' => ['general-manager', true, false],
    'General Manager' => ['General Manager', true, false],
    'manager' => ['manager', false, false],
    'Managers' => ['Managers', false, false],
    'employee' => ['employee', false, false],
    'Team Member' => ['Team Member', false, false],
    'MEAL' => ['MEAL', false, false],
    'Financial' => ['Financial', false, false],
    'Media Team' => ['Media Team', false, false],
    'Public Relations' => ['Public Relations', false, false],
    'Public Relations Manager' => ['Public Relations Manager', false, false],
]);

test('a renamed role keeps the meaning its alias gives it', function () {
    $user = userWithRole('General Manager', Employee::factory()->create()->id);
    $colleague = Employee::factory()->create(['is_active' => true]);

    Role::findByName('General Manager', 'web')->update(['name' => 'Chief Executive']);

    expect($user->fresh()->can('viewRecords', $colleague))->toBeTrue();
});

test('a role with no alias means only itself', function () {
    Role::findOrCreate('MEAL', 'web');

    expect(app(RoleAliases::class)->roleNames('general-manager'))->not->toContain('MEAL')
        ->and(app(RoleAliases::class)->roleNames('meal'))->toBe([]);
});

test('linking the aliases leaves the role and permission matrix as it was', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Role::findOrCreate('general-manager', 'web');
    DB::table('role_aliases')->delete();
    $before = rolePermissionMatrix();

    $this->artisan('permissions:sync')
        ->expectsOutputToContain('Alias added: general-manager = General Manager')
        ->assertSuccessful();

    expect(rolePermissionMatrix())->toBe($before)
        ->and(DB::table('role_aliases')->where('alias', 'general-manager')->count())->toBe(2);
});

test('a dry run links no alias', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    DB::table('role_aliases')->delete();

    $this->artisan('permissions:sync', ['--dry-run' => true])->assertSuccessful();

    expect(DB::table('role_aliases')->count())->toBe(0);
});

test('the role_aliases migration rolls back and comes back with the existing roles linked', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $before = rolePermissionMatrix();
    $migration = 'database/migrations/2026_11_20_100000_create_role_aliases_table.php';

    Artisan::call('migrate:rollback', ['--path' => $migration]);
    expect(Schema::hasTable('role_aliases'))->toBeFalse();

    Artisan::call('migrate', ['--path' => $migration]);

    expect(Schema::hasTable('role_aliases'))->toBeTrue()
        ->and(DB::table('role_aliases')->where('alias', 'super-admin')->count())->toBe(1)
        ->and(rolePermissionMatrix())->toBe($before);
});
