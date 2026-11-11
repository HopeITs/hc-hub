<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * The hub's Super Admin sees every module, so it also holds every
     * permission HR's own `super-admin` role holds (the HR pages check HR
     * permission names). Only grants are added; down() takes back the ones
     * Super Admin did not hold before.
     */
    public function up(): void
    {
        $superAdmin = Role::query()->where('name', 'Super Admin')->where('guard_name', 'web')->first();
        $hrSuperAdmin = Role::query()->where('name', 'super-admin')->where('guard_name', 'web')->first();

        if ($superAdmin === null || $hrSuperAdmin === null) {
            return;
        }

        $superAdmin->permissions()->syncWithoutDetaching($hrSuperAdmin->permissions()->pluck('permissions.id'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $superAdmin = Role::query()->where('name', 'Super Admin')->where('guard_name', 'web')->first();
        $hrSuperAdmin = Role::query()->where('name', 'super-admin')->where('guard_name', 'web')->first();

        if ($superAdmin === null || $hrSuperAdmin === null) {
            return;
        }

        $trackingOnly = Permission::query()
            ->whereIn('id', $hrSuperAdmin->permissions()->pluck('permissions.id'))
            ->whereNotIn('id', $this->trackingPermissionIds())
            ->pluck('id');

        $superAdmin->permissions()->detach($trackingOnly);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Permissions some tracking role holds, which Super Admin keeps on rollback.
     *
     * @return list<int>
     */
    private function trackingPermissionIds(): array
    {
        $trackingRoles = ['Super Admin', 'Team Member', 'Media Team', 'Public Relations', 'Public Relations Manager', 'MEAL', 'Financial', 'Managers', 'General Manager'];

        return Role::query()
            ->whereIn('name', $trackingRoles)
            ->with('permissions:id')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('id'))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
};
