<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * A proposal location now creates its new Core program or center directly
     * (the program-request bridge is gone). The tracking roles that already
     * manage programs get programs.create and centers.create, which until now
     * only HR roles held.
     */
    private const array Holders = ['Super Admin', 'General Manager', 'Managers'];

    private const array Permissions = ['programs.create', 'centers.create'];

    public function up(): void
    {
        $permissions = collect(self::Permissions)
            ->map(fn (string $name): Permission => Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        Role::query()
            ->with('permissions')
            ->whereIn('name', self::Holders)
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * The permissions themselves stay: HR roles held them before.
     */
    public function down(): void
    {
        Role::query()
            ->with('permissions')
            ->where('name', 'Managers')
            ->where('guard_name', 'web')
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo(self::Permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
