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
            Center::create([
                'name' => $center['name'],
                'city_id' => $center['city_id'],
                'project_id' => $center['project_id'],
                'address' => $center['address'],
                'is_active' => $center['is_active'],
            ]);
        }
    }
}
