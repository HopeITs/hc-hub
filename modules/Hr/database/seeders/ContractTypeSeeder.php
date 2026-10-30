<?php

namespace Modules\Hr\Database\Seeders;

use Modules\Hr\Models\ContractType;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = config('samples.sampleData.ContractType');

        foreach ($types as $type) {
            ContractType::query()->forceCreate($type);
        }
    }
}
