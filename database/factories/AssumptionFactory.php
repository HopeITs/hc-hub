<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\Assumption;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\Result;

/**
 * @extends Factory<Assumption>
 */
class AssumptionFactory extends Factory
{
    /**
     * `status` is left to the column default (holds).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'result_id' => fn (array $attributes): int => Result::factory()->create(['proposal_id' => $attributes['proposal_id']])->id,
            'statement' => fake()->sentence(),
        ];
    }
}
