<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\ProgramRequestStatus;
use Modules\Tracking\Models\ProgramRequest;
use Modules\Tracking\Models\ProposalLocation;

/**
 * @extends Factory<ProgramRequest>
 */
class ProgramRequestFactory extends Factory
{
    /**
     * A request opened by a new row without a program. `status` is left to
     * the column default (pending); use created() or declined() otherwise.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Wells', 'Alternative Energy', 'PSS', 'Case Management', 'Parenting']);

        return [
            'proposal_location_id' => ProposalLocation::factory()->isNew(),
            'name_en' => $name,
            'name_ar' => 'برنامج '.$name,
            'requested_by' => User::factory(),
        ];
    }

    /**
     * HR created the program and the admin linked it.
     */
    public function created(int $hrProgramId): static
    {
        return $this->state(['status' => ProgramRequestStatus::Created, 'hr_program_id' => $hrProgramId]);
    }

    public function declined(): static
    {
        return $this->state(['status' => ProgramRequestStatus::Declined]);
    }
}
