<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hr\Enums\BadgeType;
use Modules\Hr\Models\Badge;
use Modules\Hr\Models\Employee;

/**
 * @extends Factory<Badge>
 */
class BadgeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Only the NOT NULL columns without a database default (see
     * .ai/rules/seeders.md).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => (string) BadgeType::Reward->value,
            'date' => now()->startOfDay(),
        ];
    }
}
