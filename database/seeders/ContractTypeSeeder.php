<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractType;
use App\Enums\ContractPlaceEnum;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'type' => ['en' => 'Full Annual', 'ar' => 'سنوي كامل'],
                'place' => ContractPlaceEnum::Onsite,
                'weekly_hours' => 40,
            ],
            [
                'type' => ['en' => 'Partial Annual', 'ar' => 'سنوي جزئي'],
                'place' => ContractPlaceEnum::Onsite,
                'weekly_hours' => 20,
            ],
            [
                'type' => ['en' => 'Trial Full', 'ar' => 'تجريبي كامل'],
                'place' => ContractPlaceEnum::Onsite,
                'weekly_hours' => 40,
            ],
            [
                'type' => ['en' => 'Trial Partial', 'ar' => 'تجريبي جزئي'],
                'place' => ContractPlaceEnum::Onsite,
                'weekly_hours' => 20,
            ],
            [
                'type' => ['en' => 'Agreement Contract', 'ar' => 'عقد اتفاق'],
                'place' => ContractPlaceEnum::Online,
                'weekly_hours' => 0,
            ],
            [
                'type' => ['en' => 'Agreement Contract', 'ar' => 'عقد اتفاق'],
                'place' => ContractPlaceEnum::Onsite,
                'weekly_hours' => 0,
            ],
        ];

        foreach ($types as $type) {
            ContractType::create($type);
        }
    }
}
