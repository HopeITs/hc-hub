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
        $this->createUserWithRole(email: 'superadmin@mail.com', password: 'password', roleName: 'super-admin', employee_id: 1);

        $this->createUserWithRole(email: 'generaladmin@mail.com', password: 'password', roleName: 'general-manager', employee_id: 2);

        $this->createUserWithRole(email: 'hrmanager@mail.com', password: 'password', roleName: 'HR-SYR', employee_id: 3);

        $this->createUserWithRole(email: 'hr@mail.com', password: 'password', roleName: 'HR-ALP', employee_id: 4);

        $this->createUserWithRole(email: 'manager@mail.com', password: 'password', roleName: 'manager', employee_id: 5);

        $this->createUserWithRole(email: 'employee@mail.com', password: 'password', roleName: 'employee', employee_id: 6);
    }

    private function createUserWithRole(string $email, string $password, string $roleName, $employee_id): void
    {
        $user = User::create([
            'employee_id' => $employee_id,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole($roleName);
    }
}
