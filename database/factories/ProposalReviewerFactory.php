<?php

namespace Database\Factories;

use Database\Factories\Concerns\CreatesHrRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tracking\Enums\ReviewerSlot;
use Modules\Tracking\Enums\ReviewStage;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalReviewer;

/**
 * @extends Factory<ProposalReviewer>
 */
class ProposalReviewerFactory extends Factory
{
    use CreatesHrRecords;

    /**
     * An unseen project-owner reviewer of the concept-note stage. Use seen()
     * and additional() for the other common rows.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'stage' => ReviewStage::ConceptNote,
            'employee_id' => fn (): int => $this->createHrEmployee(),
            'slot' => ReviewerSlot::ProjectOwner,
        ];
    }

    /**
     * The reviewer has seen the proposal.
     */
    public function seen(): static
    {
        return $this->state(['seen_at' => now()]);
    }

    /**
     * Added by hand by a proposals.manage-reviewers holder.
     */
    public function additional(): static
    {
        return $this->state(['slot' => ReviewerSlot::Additional]);
    }
}
