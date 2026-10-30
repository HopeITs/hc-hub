<?php

namespace Modules\Hr\Database\Seeders;

use Modules\Hr\Models\Position;
use Illuminate\Database\Seeder;

class PositionsSeeder extends Seeder
{
    public function run(): void
    {
        $positions = config('samples.sampleData.positions');

        foreach ($positions as $position) {
            Position::create($position);
        }
    }
}
