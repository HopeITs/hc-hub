<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();



        $this->call([
            ProjectSeeder::class,
            CitySeeder::class,
            ContractTypeSeeder::class,
            DepartmentSeeder::class,
            CenterSeeder::class,
            CheckListItemSeeder::class,
            LeaveTypeSeeder::class,
            AnnualLeaveRuleSeeder::class,
            RolesPermissionsSeeder::class,
            UserRoleSeeder::class,
            JobLevelSeeder::class,
            EmployeeSeeder::class
        ]);

    //    $user = User::factory()->create([
    //         // 'name' => ['en' => 'Test User'],
    //         'email' => 'test@example.com',
    //     ]);
    }
}
