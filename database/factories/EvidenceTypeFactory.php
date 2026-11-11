<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\EvidenceType;

/**
 * `is_active` and `sort_order` carry database defaults, so the definition
 * leaves them to the database (.ai/rules/seeders.md); use `inactive()` for a
 * deactivated type.
 *
 * @extends Factory<EvidenceType>
 */
class EvidenceTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_ar' => 'نوع شاهد '.fake()->unique()->numberBetween(1, 99999),
        ];
    }

    /**
     * A type taken off new selection.
     */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
