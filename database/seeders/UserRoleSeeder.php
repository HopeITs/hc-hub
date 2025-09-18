<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->createUserWithRole(
            email: 'superadmin@mail.com',
            password: 'password',
            roleName: 'Super Admin'
        );

        $this->createUserWithRole(
            email: 'generaladmin@mail.com',
            password: 'password',
            roleName: 'General Admin'
        );

        $this->createUserWithRole(
            email: 'hr@mail.com',
            password: 'password',
            roleName: 'HR'
        );

        $this->createUserWithRole(
            email: 'manager@mail.com',
            password: 'password',
            roleName: 'Manager'
        );

        $this->createUserWithRole(
            email: 'employee@mail.com',
            password: 'password',
            roleName: 'Employee'
        );
    }

    private function createUserWithRole(string $email, string $password, string $roleName): void
    {
        $user = User::create([
            'employee_id' => Employee::factory()->create()->id,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $role = Role::where('name', $roleName)->first();

        if ($role) {
            $user->roles()->attach($role);
        } else {
            echo "Role '{$roleName}' not found.\n";
        }
    }
}
