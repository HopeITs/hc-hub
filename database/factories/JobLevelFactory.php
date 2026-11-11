<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hr\Models\JobLevel;

/**
 * @extends Factory<JobLevel>
 */
class JobLevelFactory extends Factory
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
            'name_en' => fake()->jobTitle(),
            'name_ar' => fake()->jobTitle(),
        ];
    }
}
