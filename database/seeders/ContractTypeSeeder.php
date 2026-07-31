<?php

namespace Database\Seeders;

use App\Models\ContractType;
use Illuminate\Database\Seeder;

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
