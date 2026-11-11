<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
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
            'program_id' => Program::factory()->unassigned(),
            'city_id' => City::factory(),
            'job_level_id' => JobLevel::factory(),
            'department_id' => Department::factory(),
            'title_en' => fake()->jobTitle(),
            'title_ar' => fake()->jobTitle(),
        ];
    }
}
