<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hr\Enums\ContractAmendmentStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractAmendment;

/**
 * @extends Factory<ContractAmendment>
 */
class ContractAmendmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Only the NOT NULL columns without a database default (see
     * .ai/rules/seeders.md): a first draft that changes the weekly hours.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'number' => 1,
            'effective_from' => now()->startOfDay(),
            'terms_before' => ['weekly_hours' => 20],
            'terms_after' => ['weekly_hours' => 40],
        ];
    }

    /**
     * Signed and approved, waiting for its effective date.
     */
    public function approved(): static
    {
        return $this->state([
            'status' => ContractAmendmentStatus::Approved->value,
            'approved_at' => now(),
        ]);
    }
}
