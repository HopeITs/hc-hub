<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RequiredPosition;

class RequiredPositionsSeeder extends Seeder
{
    public function run(): void
    {
        $positions = config('samples.sampleData.requiredPositions');

        foreach ($positions as $position) {
            RequiredPosition::create($position);
        }
    }
}
