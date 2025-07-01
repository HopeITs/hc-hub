<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = config('samples.sampleData.cities');

        foreach ($cities as $city) {
            City::create([
                'name' => $city['name'],
                'code' => $city['code'],
                'is_active' => $city['is_active'],
            ]);
        }
    }
}
