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
            $data = [
                'name' => $center['name'],
                'city_id' => $center['city_id'],
                'is_active' => $center['is_active'],
                'address' => is_string($center['address']) ? ['en' => $center['address'], 'ar' => $center['address']] : $center['address'],
            ];

            Center::create($data);
        }
    }
}
