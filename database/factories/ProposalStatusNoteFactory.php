<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Enums\ProposalStatus;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalStatusNote;

/**
 * @extends Factory<ProposalStatusNote>
 */
class ProposalStatusNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * `date` is left to the column default so a note is stamped today.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'status' => fake()->randomElement(ProposalStatus::live()),
            'context' => fake()->sentence(),
        ];
    }
}
