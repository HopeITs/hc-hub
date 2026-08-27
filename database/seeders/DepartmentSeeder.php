<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

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
