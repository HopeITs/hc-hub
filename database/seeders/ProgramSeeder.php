<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = config('samples.sampleData.programs');

        foreach ($programs as $program) {
            Program::create($program);
        }
    }
}
