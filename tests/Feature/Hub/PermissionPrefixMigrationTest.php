<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessPermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

function permissionPrefixMigration(): Migration
{
    return require database_path('migrations/2026_10_29_000002_prefix_permission_names_by_module.php');
}

/**
 * @return array<int, string> permission id => name
 */
function permissionNamesById(): array
{
    return DB::table('permissions')->pluck('name', 'id')->all();
}

test('the frozen mapping covers exactly the catalogue', function () {
    $renames = permissionPrefixMigration()::renames();

    expect(array_values($renames))->toEqualCanonicalizing(AccessPermissionCatalog::names())
        ->and($renames['contracts.export'])->toBe('hr.contracts.export')
        ->and($renames['access-management.manage-roles'])->toBe('hub.access-management.manage-roles')
        ->and($renames['activity-log.view'])->toBe('hub.activity-log.view');
});

test('down restores the old names and up renames them again, keeping every grant', function () {
    $migration = permissionPrefixMigration();
    $role = Role::findOrCreate('rename-probe', 'web');
    $user = User::factory()->create();
    $role->givePermissionTo('hr.contracts.export', 'hub.access-management.view');
    $user->givePermissionTo('hr.leaves.decide');

    $export = Permission::findByName('hr.contracts.export', 'web');
    $access = Permission::findByName('hub.access-management.view', 'web');
    $decide = Permission::findByName('hr.leaves.decide', 'web');
    $roleGrants = DB::table('role_has_permissions')->where('role_id', $role->id)->pluck('permission_id')->sort()->values()->all();
    $userGrants = DB::table('model_has_permissions')->where('model_id', $user->id)->pluck('permission_id')->all();
    $count = DB::table('permissions')->count();

    $migration->down();

    $names = permissionNamesById();
    expect($names[$export->id])->toBe('contracts.export')
        ->and($names[$access->id])->toBe('access-management.view')
        ->and($names[$decide->id])->toBe('leaves.decide')
        ->and(collect($names)->filter(fn (string $name) => str_starts_with($name, 'hr.') || str_starts_with($name, 'hub.')))->toBeEmpty();

    $migration->up();

    $names = permissionNamesById();
    expect($names[$export->id])->toBe('hr.contracts.export')
        ->and($names[$access->id])->toBe('hub.access-management.view')
        ->and($names[$decide->id])->toBe('hr.leaves.decide')
        ->and(DB::table('permissions')->count())->toBe($count)
        ->and(DB::table('role_has_permissions')->where('role_id', $role->id)->pluck('permission_id')->sort()->values()->all())->toBe($roleGrants)
        ->and(DB::table('model_has_permissions')->where('model_id', $user->id)->pluck('permission_id')->all())->toBe($userGrants);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $user = $user->fresh();
    $user->roles()->attach($role->id, ['is_active' => true]);

    expect($user->fresh()->hasActivePermission('hr.contracts.export'))->toBeTrue()
        ->and($user->fresh()->hasActivePermission('hr.leaves.decide'))->toBeTrue();
});

test('up is idempotent and skips names that do not exist', function () {
    $migration = permissionPrefixMigration();
    DB::table('permissions')->where('name', 'hr.badges.delete')->delete();
    $before = permissionNamesById();

    $migration->up();
    $migration->up();

    expect(permissionNamesById())->toBe($before);
});

test('when both names exist the old row merges its grants into the new one', function () {
    $migration = permissionPrefixMigration();
    $new = Permission::findByName('hr.news.create', 'web');
    $old = Permission::create(['name' => 'news.create', 'guard_name' => 'web']);
    $oldOnly = Role::findOrCreate('merge-old-only', 'web');
    $both = Role::findOrCreate('merge-both', 'web');
    $user = User::factory()->create();

    DB::table('role_has_permissions')->insert([
        ['permission_id' => $old->id, 'role_id' => $oldOnly->id],
        ['permission_id' => $old->id, 'role_id' => $both->id],
        ['permission_id' => $new->id, 'role_id' => $both->id],
    ]);
    DB::table('model_has_permissions')->insert([
        'permission_id' => $old->id,
        'model_type' => $user->getMorphClass(),
        'model_id' => $user->id,
    ]);

    $migration->up();

    expect(DB::table('permissions')->where('id', $old->id)->exists())->toBeFalse()
        ->and(DB::table('role_has_permissions')->where('permission_id', $new->id)->whereIn('role_id', [$oldOnly->id, $both->id])->count())->toBe(2)
        ->and(DB::table('model_has_permissions')->where('permission_id', $new->id)->where('model_id', $user->id)->exists())->toBeTrue()
        ->and(DB::table('role_has_permissions')->where('permission_id', $old->id)->exists())->toBeFalse();
});
