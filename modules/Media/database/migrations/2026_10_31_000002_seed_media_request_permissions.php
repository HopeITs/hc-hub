<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/*
 * The media.requests.* permissions on a database that was already migrated
 * when the Media module arrived (2026_07_30_000001_seed_access_control_permissions
 * reads the catalogue at run time, so a fresh database gets them there too).
 *
 * The lists are frozen here on purpose: later catalogue edits go through
 * Access Management or their own migration. Idempotent.
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'media.requests.view-own', 'media.requests.create', 'media.requests.approve', 'media.requests.work',
        'media.requests.manage', 'media.requests.view-log', 'media.requests.export',
    ];

    private const BASELINES = [
        'general-manager' => self::PERMISSIONS,
        'manager' => self::PERMISSIONS,
        'employee' => ['media.requests.view-own', 'media.requests.create', 'media.requests.work', 'media.requests.approve', 'media.requests.manage'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::PERMISSIONS)->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'));

        foreach (config('access-control.administrator_roles', []) as $roleName) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo($permissions);
        }

        foreach (self::BASELINES as $roleName => $names) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo($names);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', self::PERMISSIONS)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
