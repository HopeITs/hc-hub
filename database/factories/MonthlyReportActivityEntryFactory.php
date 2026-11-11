<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Enums\ResultType;
use Modules\Tracking\Models\MonthlyReport;
use Modules\Tracking\Models\MonthlyReportActivityEntry;
use Modules\Tracking\Models\Result;

/**
 * @extends Factory<MonthlyReportActivityEntry>
 */
class MonthlyReportActivityEntryFactory extends Factory
{
    /**
     * An activity listed in a report with no status picked yet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monthly_report_id' => MonthlyReport::factory(),
            'result_id' => fn (): int => Result::factory()->activity(Result::factory()->type(ResultType::Output)->create())->create()->id,
        ];
    }
}
