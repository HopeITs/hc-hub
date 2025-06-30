<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'Aleppo',   'code' => 'ALE'],
            ['name' => 'Damascus', 'code' => 'DAM'],
            ['name' => 'Homs',     'code' => 'HOM'],
            ['name' => 'Latakia',  'code' => 'LAT'],
            ['name' => 'Tartus',   'code' => 'TAR'],
        ];

        foreach ($cities as $city) {
            City::create([
                'name' => $city['name'],
                'code' => $city['code'],
                'is_active' => true,
            ]);
        }
    }
}
