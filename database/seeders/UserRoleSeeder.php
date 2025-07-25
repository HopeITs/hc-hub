<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create or get the user
        $user = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => ['en' => 'Super Admin'],
                'password' => 'password'
            ]
        );
        $role = Role::where('name', 'Super Admin')->first();
        if ($role) {
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
