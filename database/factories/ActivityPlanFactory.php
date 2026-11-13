<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Program;
use Modules\Tracking\Enums\ActivityPlanStatus;
use Modules\Tracking\Models\ActivityPlan;
use Modules\Tracking\Models\Proposal;

/**
 * @extends Factory<ActivityPlan>
 */
class ActivityPlanFactory extends Factory
{
    /**
     * A draft plan of a new program in a new proposal.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'program_id' => Program::factory(),
        ];
    }

    public function inReview(): static
    {
        return $this->state([
            'status' => ActivityPlanStatus::InReview->value,
            'step' => 3,
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => ActivityPlanStatus::Approved->value,
            'step' => 3,
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
        ]);
    }
}
