<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $baselines = [
            'general-manager' => ['hr.project-basics.view', 'hr.checklists.manage'],
            'manager' => ['hr.project-basics.view'],
            'employee' => ['hr.project-basics.view'],
        ];

        foreach ($baselines as $roleName => $permissions) {
            Role::findOrCreate($roleName, 'web')->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Additive baseline grants are intentionally preserved on rollback.
    }
};
