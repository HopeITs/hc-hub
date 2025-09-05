<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([CitySeeder::class, ContractTypeSeeder::class, DepartmentSeeder::class, CheckListItemSeeder::class, LeaveTypeSeeder::class, AnnualLeaveRuleSeeder::class, RolesPermissionsSeeder::class, JobLevelSeeder::class, EmployeeSeeder::class, UserRoleSeeder::class, ProjectSeeder::class, CenterSeeder::class, RequiredPositionsSeeder::class]);
    }
}
