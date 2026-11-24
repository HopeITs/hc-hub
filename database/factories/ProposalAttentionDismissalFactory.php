<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\User;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalAttentionDismissal;

/**
 * @extends Factory<ProposalAttentionDismissal>
 */
class ProposalAttentionDismissalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'proposal_id' => Proposal::factory(),
            'item_key' => 'item-'.fake()->unique()->numberBetween(1, 99999),
        ];
    }
}
