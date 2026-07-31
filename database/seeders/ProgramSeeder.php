<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

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
