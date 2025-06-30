<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'HeadOffice/ Aleppo',   'description' => null, 'email' => 'headoffice@hcsyria.org'],
            ['name' => 'Front Desk',           'description' => null, 'email' => null],
            ['name' => 'Teachers',             'description' => null, 'email' => null],
            ['name' => 'Trainers/ Coach',      'description' => null, 'email' => null],
            ['name' => 'IT',                   'description' => null, 'email' => 'it@hcsyria.org'],
            ['name' => 'Communication',        'description' => null, 'email' => 'ct@hcsyria.org'],
            ['name' => 'HR',                   'description' => null, 'email' => null],
            ['name' => 'Finance',              'description' => null, 'email' => 'finance@hcsyria.org'],
            ['name' => 'Meal',                 'description' => null, 'email' => null],
            ['name' => 'Fundraising',          'description' => null, 'email' => null],
            ['name' => 'Logistic',             'description' => null, 'email' => null],
            ['name' => 'Legal Affairs',        'description' => null, 'email' => null],
            ['name' => 'Services/ Keepers',    'description' => null, 'email' => null],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}
