<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Enums\Gender;
use Modules\Tracking\Models\MonthlyReportLine;
use Modules\Tracking\Models\MonthlyReportLineBreakdown;

/**
 * @extends Factory<MonthlyReportLineBreakdown>
 */
class MonthlyReportLineBreakdownFactory extends Factory
{
    /**
     * A line-level male count with persons with disability among them.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monthly_report_line_id' => MonthlyReportLine::factory(),
            'gender' => Gender::Male,
            'value' => '10.00',
            'disabled_value' => '2.00',
        ];
    }
}
