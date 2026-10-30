<?php

namespace Modules\Hr\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EducationFieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = config('samples.sampleData.education_fields');

        foreach ($fields as $field) {
            DB::table('education_fields')->insert([
                'name' => $field,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
