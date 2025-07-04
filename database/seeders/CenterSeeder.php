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
            $center['name'] = is_array($center['name']) ? $center['name'] : ['en' => $center['name']];
            $center['address'] = is_array($center['address']) ? $center['address'] : ['en' => $center['address'], 'ar' => $center['address']];

            Center::create($center);
        }
    }
}
