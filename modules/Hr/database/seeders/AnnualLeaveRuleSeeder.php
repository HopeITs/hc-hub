<?php

namespace Modules\Hr\Database\Seeders;

use Modules\Hr\Models\AnnualLeaveRule;
use Illuminate\Database\Seeder;

class AnnualLeaveRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = config('samples.sampleData.annualLeaveRules');

        foreach ($rules as $rule) {
            AnnualLeaveRule::create($rule);
        }
    }
}
