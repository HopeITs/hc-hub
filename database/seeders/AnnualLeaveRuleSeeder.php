<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AnnualLeaveRule;

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
