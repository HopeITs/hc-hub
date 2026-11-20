<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

/**
 * Three rules that named a role inside a policy become permissions (roadmap
 * 4.3), each granted to the one role the policy named, so nobody's access
 * changes:
 *  - meal-coverage.stand-in: MEAL (covers a project with no responsible officer);
 *  - reports.reopen-stand-in: Public Relations Manager (decides reopen
 *    requests while there is no MEAL manager);
 *  - strategies.approve-unmanaged: General Manager (approves the strategic
 *    plan of a program that has no manager).
 * The administrator roles hold every permission, so they get all three.
 */
return new class extends Migration
{
    private const GRANTS = [
        'meal-coverage.stand-in' => 'MEAL',
        'reports.reopen-stand-in' => 'Public Relations Manager',
        'strategies.approve-unmanaged' => 'General Manager',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $administrators = config('access-control.administrator_roles', []);

        foreach (self::GRANTS as $name => $roleName) {
            $permissionId = (int) Permission::findOrCreate($name, 'web')->getKey();

            foreach (Role::query()->where('guard_name', 'web')->whereIn('name', [$roleName, ...$administrators])->get() as $role) {
                $role->permissions()->syncWithoutDetaching([$permissionId]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', array_keys(self::GRANTS))->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
