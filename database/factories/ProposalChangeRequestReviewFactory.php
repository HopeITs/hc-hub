<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Models\ProposalChangeRequest;
use Modules\Tracking\Models\ProposalChangeRequestReview;

/**
 * @extends Factory<ProposalChangeRequestReview>
 */
class ProposalChangeRequestReviewFactory extends Factory
{
    /**
     * A waiting Finance Manager review.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_change_request_id' => ProposalChangeRequest::factory(),
            'slot' => 'finance_manager',
        ];
    }
}
