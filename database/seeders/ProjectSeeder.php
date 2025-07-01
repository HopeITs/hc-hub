<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $projects = config('samples.sampleData.projects');

        foreach ($projects as $project) {
            Project::create($project);
        }
    }
}
