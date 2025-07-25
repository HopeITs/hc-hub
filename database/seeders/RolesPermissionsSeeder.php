<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        //Roles:
        $superAdminRole = Role::create(['name' => 'Super Admin']);
        $generalAdminRole = Role::create(['name' => 'General Manger']);
        $hrManagerRole = Role::create(['name' => 'HR Manager']);
        $hrRole = Role::create(['name' => 'HR']);
        $employeeRole = Role::create(['name' => 'Employee']);

        //Here we can assign permissions to each role when we know it:
        //     $superAdminPermissions = [];

        //     $generalAdminPermissions = [];

        //     $hrManagerPermissions = [];

        //     $hrPermissions = [];

        //     $employeePermissions = [];

        //     foreach ($superAdminPermissions as $permission) {
        //         $perm = Permission::create(['name' => $permission]);
        //         $superAdminRole->permissions()->attach($perm->id);
        //     }

        //     foreach ($generalAdminPermissions as $permission) {
        //         $perm = Permission::create(['name' => $permission]);
        //         $generalAdminRole->permissions()->attach($perm->id);
        //     }

        //     foreach ($hrManagerPermissions as $permission) {
        //         $perm = Permission::create(['name' => $permission]);
        //         $hrManagerRole->permissions()->attach($perm->id);
        //     }

        //     foreach ($hrPermissions as $permission) {
        //         $perm = Permission::create(['name' => $permission]);
        //         $hrRole->permissions()->attach($perm->id);
        //     }

        //     foreach ($employeePermissions as $permission) {
        //         $perm = Permission::create(['name' => $permission]);
        //         $employeeRole->permissions()->attach($perm->id);
        //     }
        // }
    }
}
