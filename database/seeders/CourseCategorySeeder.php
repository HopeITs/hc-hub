<?php

namespace Database\Seeders;

use App\Models\CourseCategory;
use Illuminate\Database\Seeder;

class CourseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $courseCategories = config('samples.sampleData.courseCategories');

        foreach ($courseCategories as $category) {
            $existing = CourseCategory::where('name_en', $category['name_en'])->first();

            if (! $existing) {
                CourseCategory::create($category);
            }
        }
    }
}
