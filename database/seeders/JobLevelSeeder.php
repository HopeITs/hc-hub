<?php

namespace Database\Seeders;

use App\Models\JobLevel;
use Illuminate\Database\Seeder;

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
