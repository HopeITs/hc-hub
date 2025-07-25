<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->createUserWithRole(
            name: ['en' => 'Rita'],
            email: 'superadmin@mail.com',
            password: 'password',
            roleName: 'Super Admin'
        );

        $this->createUserWithRole(
            name: ['en' => 'George'],
            email: 'generalmanager@mail.com',
            password: 'passowrd',
            roleName: 'General Manger'
        );

        $this->createUserWithRole(
            name: ['en' => 'Yousef'],
            email: 'hrmanager@mail.com',
            password: 'password',
            roleName: 'HR Manager'
        );

        $this->createUserWithRole(
            name: ['en' => 'Mirna'],
            email: 'hr@mail.com',
            password: 'password',
            roleName: 'HR'
        );

        $this->createUserWithRole(
            name: ['en' => 'example'],
            email: 'employee@mail.com',
            password: 'password',
            roleName: 'Employee'
        );
    }

    private function createUserWithRole(array $name, string $email, string $password, string $roleName): void
    {
        $user = User::create([
            'name' => $name,
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
