<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Enums\IndicatorCollector;
use Modules\Tracking\Models\LibraryIndicator;
use Modules\Tracking\Models\MonthlyReport;
use Modules\Tracking\Models\MonthlyReportLine;

/**
 * @extends Factory<MonthlyReportLine>
 */
class MonthlyReportLineFactory extends Factory
{
    /**
     * An empty project-team line; `not_measured` is left to the column default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monthly_report_id' => MonthlyReport::factory(),
            'library_indicator_id' => LibraryIndicator::factory(),
            'entered_by' => IndicatorCollector::ProjectTeam,
        ];
    }

    /**
     * A line MEAL fills during validation (D-48a).
     */
    public function meal(): static
    {
        return $this->state(['entered_by' => IndicatorCollector::Meal]);
    }
}
