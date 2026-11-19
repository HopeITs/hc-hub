<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\User;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalChangeRequest;

/**
 * @extends Factory<ProposalChangeRequest>
 */
class ProposalChangeRequestFactory extends Factory
{
    /**
     * A pending request to rename an approved proposal. No review rows: use
     * withReviews() when the test needs them.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory()->running(),
            'requested_by' => User::factory(),
            'reason' => 'The donor renamed the grant in the signed agreement.',
            'changes' => [['field' => 'name', 'from' => 'Old name', 'to' => 'New name']],
            'payload' => ['name' => 'New name'],
        ];
    }

    /**
     * Both review rows, waiting.
     */
    public function withReviews(): static
    {
        return $this->afterCreating(function (ProposalChangeRequest $request): void {
            foreach (ProposalChangeRequest::ReviewSlots as $slot) {
                $request->reviews()->create(['slot' => $slot]);
            }
        });
    }
}
