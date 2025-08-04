<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobLevel;

class JobLevelSeeder extends Seeder
{
    public function run(): void
    {
        $jobLevels = config('samples.sampleData.jobLevels');

        foreach ($jobLevels as $level) {
            JobLevel::create($level);
        }
    }
}
