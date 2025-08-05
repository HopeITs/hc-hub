<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;


class EmployeeSeeder extends Seeder
{
    public function run(): void
    {

        $employees = config('samples.sampleData.employees');
        foreach ($employees as $emp) {
            Employee::create($emp);
        }
    }
}
