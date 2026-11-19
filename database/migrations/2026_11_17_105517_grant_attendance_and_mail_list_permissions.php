<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Modules\Core\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * The HR app's attendance and mail-list permissions, granted as it grants them:
 *  - attendance.view-own: every role (an employee sees their own timesheet);
 *  - attendance.view: the HR roles, managers (their direct reports), the general manager and the
 *    administrators (Modules\Hr\Support\Attendance\Roster scopes it by the active role);
 *  - attendance.manage: upload the device report, link numbers, lock months (HR, administrators);
 *  - mail-lists.manage: edit the members of a mailing list (HR, administrators);
 *  - mail-lists.confirm: one person's, never a role's (whoever holds the mail provider's control
 *    panel); given to that account when it exists, else left for Access Management.
 * Only grants are added; hub:import-hr mirrors HR's own role grants afterwards anyway.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['attendance.view-own', 'attendance.view', 'attendance.manage', 'mail-lists.manage', 'mail-lists.confirm'];

    private const CONFIRMER_EMAIL = 'mai.youssef@hcsyria.org';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $administrators = ['super-admin', ...config('access-control.administrator_roles', [])];
        $hr = Role::query()->where('name', 'like', 'HR-%')->orWhereIn('name', $administrators)->with('permissions')->get();

        foreach (Role::query()->with('permissions')->get() as $role) {
            $role->givePermissionTo('attendance.view-own');
        }

        foreach ($hr as $role) {
            $role->givePermissionTo(['attendance.view', 'attendance.manage', 'mail-lists.manage']);
        }

        foreach (Role::query()->whereIn('name', ['manager', 'general-manager', 'Managers', 'General Manager'])->with('permissions')->get() as $role) {
            $role->givePermissionTo('attendance.view');
        }

        User::query()->with(['permissions', 'roles'])->where('email', self::CONFIRMER_EMAIL)->first()?->givePermissionTo('mail-lists.confirm');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->whereIn('name', self::PERMISSIONS)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
