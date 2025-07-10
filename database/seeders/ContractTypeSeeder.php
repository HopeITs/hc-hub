<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractType;


class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = config('samples.sampleData.ContractType');

        foreach ($types as $type) {
            ContractType::create($type);
        }
    }
}
