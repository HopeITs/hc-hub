<?php

namespace Database\Seeders;

use App\Models\Center;
use Illuminate\Database\Seeder;

class CenterSeeder extends Seeder
{
    public function run(): void
    {
        $centers = config('samples.sampleData.centers');

        foreach ($centers as $center) {
            Center::create($center);
        }
    }
}
