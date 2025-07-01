<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = config('samples.sampleData.departments');

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}
