<?php

namespace Database\Factories;

use Database\Factories\Concerns\CreatesHrRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\Supervision;

/**
 * @extends Factory<Supervision>
 */
class SupervisionFactory extends Factory
{
    use CreatesHrRecords;

    /**
     * Define the model's default state: one new HR employee supervising another.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supervisor_employee_id' => fn (): int => $this->createHrEmployee(),
            'supervisee_employee_id' => fn (): int => $this->createHrEmployee(),
        ];
    }
}
