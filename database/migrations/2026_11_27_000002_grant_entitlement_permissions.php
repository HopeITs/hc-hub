<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions Library (meeting 19/11, track 5):
 *  - entitlements.manage-library: the HR roles and the administrators edit the
 *    library and link it to positions;
 *  - entitlements.assign: the HR roles (their city), the administrators and
 *    managers (their direct reports only) add, mark done and revoke a person's items.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $library = Permission::findOrCreate('entitlements.manage-library', 'web');
        $assign = Permission::findOrCreate('entitlements.assign', 'web');

        $administrators = ['super-admin', ...config('access-control.administrator_roles', [])];

        $hr = Role::query()
            ->where('name', 'like', 'HR-%')
            ->orWhereIn('name', $administrators)
            ->with('permissions')
            ->get();

        foreach ($hr as $role) {
            $role->givePermissionTo($library, $assign);
        }

        Role::query()->whereIn('name', ['manager', 'Managers'])->with('permissions')->get()
            ->each(fn (Role $role) => $role->givePermissionTo($assign));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', ['entitlements.manage-library', 'entitlements.assign'])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
