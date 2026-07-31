<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {

        $employees = config('samples.sampleData.employees');

        foreach ($employees as $employee) {
            Employee::create($employee);
        }

        //        Employee::factory(25)->create();
    }
}
