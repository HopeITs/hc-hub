<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hr\Models\Employee;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
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
            'first_name_en' => fake()->firstName(),
            'first_name_ar' => fake()->firstName(),
            'last_name_en' => fake()->lastName(),
            'last_name_ar' => fake()->lastName(),
        ];
    }

    /**
     * Indicate a former staff member.
     */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
