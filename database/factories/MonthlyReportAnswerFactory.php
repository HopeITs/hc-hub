<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\MonthlyReport;
use Modules\Tracking\Models\MonthlyReportAnswer;

/**
 * @extends Factory<MonthlyReportAnswer>
 */
class MonthlyReportAnswerFactory extends Factory
{
    /**
     * An answer to a question no longer linked to a template question.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monthly_report_id' => MonthlyReport::factory(),
            'report_template_question_id' => null,
            'question_text' => 'ما أبرز ما تحقق هذا الشهر؟',
            'answer' => fake()->sentence(),
        ];
    }
}
