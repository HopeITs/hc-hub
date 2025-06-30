<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            //Active cities where we have centers:
            ['code' => 'ALE', 'name' => ['en' => 'Aleppo', 'ar' => 'حلب'],        'is_active' => true],
            ['code' => 'DAM', 'name' => ['en' => 'Damascus', 'ar' => 'دمشق'],     'is_active' => true],
            ['code' => 'HOM', 'name' => ['en' => 'Homs', 'ar' => 'حمص'],          'is_active' => true],
            ['code' => 'LAT', 'name' => ['en' => 'Latakia', 'ar' => 'اللاذقية'], 'is_active' => true],
            ['code' => 'TAR', 'name' => ['en' => 'Tartus', 'ar' => 'طرطوس'],      'is_active' => true],

            //Non active cities:
            ['code' => 'HSA', 'name' => ['en' => 'Hasakah', 'ar' => 'الحسكة'],       'is_active' => false],
            ['code' => 'DER', 'name' => ['en' => 'Deir el-zor', 'ar' => 'دير الزور'],'is_active' => false],
            ['code' => 'RAQ', 'name' => ['en' => 'Raqqa', 'ar' => 'الرقة'],          'is_active' => false],
            ['code' => 'IDB', 'name' => ['en' => 'Edleb', 'ar' => 'إدلب'],           'is_active' => false],
            ['code' => 'SUW', 'name' => ['en' => 'Suwaida', 'ar' => 'السويداء'],  'is_active' => false],
            ['code' => 'DAR', 'name' => ['en' => 'Daraa', 'ar' => 'درعا'],           'is_active' => false],
            ['code' => 'QUN', 'name' => ['en' => 'Quneitra', 'ar' => 'القنيطرة'],    'is_active' => false],
            ['code' => 'RIF', 'name' => ['en' => 'Rif Dimashq', 'ar' => 'ريف دمشق'], 'is_active' => false],
            ['code' => 'HAM', 'name' => ['en' => 'Hama', 'ar' => 'حماة'],            'is_active' => false],
        ];

        foreach ($cities as $city) {
            City::create([
                'name' => $city['name'],
                'code' => $city['code'],
                'is_active' => $city['is_active'],
            ]);
        }
    }
}
