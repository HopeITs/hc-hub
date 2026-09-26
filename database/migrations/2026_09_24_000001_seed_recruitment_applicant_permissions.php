<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Applicant permissions go to HR only.
 *
 * Managers hold recruitment-campaigns.view to follow their own campaigns, but
 * applicant records, CVs and internal notes are HR's. No role baseline carries
 * these, so manager and general-manager get none of them here.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = array_map(
            fn (string $action): string => "recruitment-applicants.{$action}",
            config('access-control.groups.recruitment-applicants', []),
        );

        if ($names === []) {
            return;
        }

        foreach ($names as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (config('access-control.administrator_roles', []) as $roleName) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo($names);
        }

        Role::query()
            ->where('name', 'like', 'HR-%')
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($names));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('name', 'like', 'recruitment-applicants.%')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
