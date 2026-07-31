<?php

namespace Database\Seeders;

use App\Models\AnnualLeaveRule;
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
