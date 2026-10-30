<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Hr\Database\Seeders\AnnualLeaveRuleSeeder;
use Modules\Hr\Database\Seeders\CheckListItemSeeder;
use Modules\Hr\Database\Seeders\ContractTypeSeeder;
use Modules\Hr\Database\Seeders\CourseCategorySeeder;
use Modules\Hr\Database\Seeders\EducationFieldSeeder;
use Modules\Hr\Database\Seeders\EmployeeContractSeeder;
use Modules\Hr\Database\Seeders\EmployeeSeeder;
use Modules\Hr\Database\Seeders\JobLevelSeeder;
use Modules\Hr\Database\Seeders\LeaveTypeSeeder;
use Modules\Hr\Database\Seeders\PositionsSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Modules\Hr\Database\Seeders\TerminationReasonSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            ProgramSeeder::class,
            CitySeeder::class,
            ContractTypeSeeder::class,
            DepartmentSeeder::class,
            CenterSeeder::class,
            CheckListItemSeeder::class,
            LeaveTypeSeeder::class,
            AnnualLeaveRuleSeeder::class,
            RolesPermissionsSeeder::class,
            JobLevelSeeder::class,
            PositionsSeeder::class,
            EmployeeSeeder::class,
            UserRoleSeeder::class,
            EmployeeContractSeeder::class,
            EducationFieldSeeder::class,
            CourseCategorySeeder::class,
            TerminationReasonSeeder::class,
        ]);

        //    $user = User::factory()->create([
        //         // 'name' => ['en' => 'Test User'],
        //         'email' => 'test@example.com',
        //     ]);
    }
}
