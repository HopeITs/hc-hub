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
                'type' => 'Full Annual',
                'place' => 'Onsite',
                'weekly_hours' => 40,
            ],
            [
                'type' => 'Partial Annual',
                'place' => 'Onsite',
                'weekly_hours' => 20,
            ],
            [
                'type' => 'Trial',
                'place' => 'Onsite',
                'weekly_hours' => 40,
            ],
            [
                'type' => 'Trial',
                'place' => 'Onsite',
                'weekly_hours' => 20,
            ],
            [
                'type' => 'Agreement Contract',
                'place' => 'Online',
                'weekly_hours' => 0,
            ],
            [
                'type' => 'Agreement Contract',
                'place' => 'Onsite',
                'weekly_hours' => 0,
            ],
        ];

        foreach ($types as $type) {
            ContractType::create($type);
        }
    }
}
