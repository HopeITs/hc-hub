<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractType;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'type' => ['en' => 'Full Annual', 'ar' => 'سنوي كامل'],
                'place' => ['en' => 'Onsite', 'ar' => 'بالموقع'],
                'weekly_hours' => 40,
            ],
            [
                'type' => ['en' => 'Partial Annual', 'ar' => 'سنوي جزئي'],
                'place' => ['en' => 'Onsite', 'ar' => 'بالموقع'],
                'weekly_hours' => 20,
            ],
            [
                'type' => ['en' => 'Trial Full', 'ar' => 'تجريبي كامل'],
                'place' => ['en' => 'Onsite', 'ar' => 'بالموقع'],
                'weekly_hours' => 40,
            ],
            [
                'type' => ['en' => 'Trial Partial', 'ar' => 'تجريبي جزئي'],
                'place' => ['en' => 'Onsite', 'ar' => 'بالموقع'],
                'weekly_hours' => 20,
            ],
            [
                'type' => ['en' => 'Agreement Contract', 'ar' => 'عقد اتفاق'],
                'place' => ['en' => 'Online', 'ar' => 'عن بعد'],
                'weekly_hours' => 0,
            ],
            [
                'type' => ['en' => 'Agreement Contract', 'ar' => 'عقد اتفاق'],
                'place' => ['en' => 'Onsite', 'ar' => 'بالموقع'],
                'weekly_hours' => 0,
            ],
        ];

        foreach ($types as $type) {
            ContractType::create($type);
        }
    }
}
