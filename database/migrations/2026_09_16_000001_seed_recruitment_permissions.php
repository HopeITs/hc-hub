<?php

use App\Models\Permission;
use App\Models\Role;
use App\Support\AccessPermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = collect(AccessPermissionCatalog::names())
            ->filter(fn (string $name): bool => str_starts_with($name, 'recruitment.'))
            ->values();

        $permissions = $permissionNames
            ->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'));

        foreach (config('access-control.administrator_roles', []) as $roleName) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo($permissions);
        }

        foreach (Role::query()->where('name', 'like', 'HR-%')->get() as $role) {
            $role->givePermissionTo($permissions);
        }

        foreach (config('access-control.role_baselines', []) as $roleName => $baseline) {
            $recruitmentBaseline = collect($baseline)
                ->filter(fn (string $name): bool => str_starts_with($name, 'recruitment.'))
                ->values()
                ->all();

            if ($recruitmentBaseline !== []) {
                Role::findOrCreate($roleName, 'web')->givePermissionTo($recruitmentBaseline);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('name', 'like', 'recruitment.%')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
