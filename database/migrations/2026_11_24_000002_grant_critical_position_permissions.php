<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Critical positions:
 *  - positions.mark-critical: HR roles and the administrators set or clear the tag;
 *  - positions.view-replacements: the suggested-replacements panel, HR roles (their
 *    city), the general manager and the administrators. Never managers or employees.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $mark = Permission::findOrCreate('positions.mark-critical', 'web');
        $view = Permission::findOrCreate('positions.view-replacements', 'web');

        $administrators = ['super-admin', ...config('access-control.administrator_roles', [])];

        $hr = Role::query()
            ->where('name', 'like', 'HR-%')
            ->orWhereIn('name', $administrators)
            ->with('permissions')
            ->get();

        foreach ($hr as $role) {
            $role->givePermissionTo($mark, $view);
        }

        Role::query()->whereIn('name', ['general-manager', 'General Manager'])->with('permissions')->get()
            ->each(fn (Role $role) => $role->givePermissionTo($view));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', ['positions.mark-critical', 'positions.view-replacements'])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
