<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * The HR app's message-template permissions, granted as it grants them:
 *  - message-templates.manage: write and edit the email / WhatsApp templates;
 *  - message-templates.use: pick one on a person's profile.
 * Both go to every HR role and the administrators; anyone else (a manager,
 * say) is granted `use` per user from Access Management. Only grants are
 * added; hub:import-hr mirrors HR's own role grants afterwards anyway.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['message-templates.manage', 'message-templates.use'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionIds = array_map(fn (string $name): int => (int) Permission::findOrCreate($name, 'web')->getKey(), self::PERMISSIONS);

        $administrators = ['super-admin', ...config('access-control.administrator_roles', [])];

        foreach (Role::query()->where('name', 'like', 'HR-%')->orWhereIn('name', $administrators)->get() as $role) {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', self::PERMISSIONS)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
