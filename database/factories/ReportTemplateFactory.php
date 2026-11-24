<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\ReportTemplateStatus;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalLocation;
use Modules\Tracking\Models\ReportTemplate;
use Modules\Tracking\Support\Reporting\ReportPlace;

/**
 * `status` carries a database default (`pending`), so the definition leaves
 * it to the database (.ai/rules/seeders.md); use `confirmed()` for a
 * confirmed template.
 *
 * @extends Factory<ReportTemplate>
 */
class ReportTemplateFactory extends Factory
{
    /**
     * Define the model's default state: a pending template.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory()->approved(),
            'city_id' => fn (array $attributes): int => (int) Project::query()->whereKey($attributes['project_id'])->value('city_id'),
            'program_id' => fn (array $attributes): int => (int) Project::query()->whereKey($attributes['project_id'])->value('program_id'),
            // As Y-02's backfill: a legacy template takes its project's center.
            'center_id' => fn (array $attributes): ?int => Project::query()->whereKey($attributes['project_id'])->value('center_id'),
        ];
    }

    /**
     * A legacy template (one with `project_id`) is carried, like the ones Y-02
     * backfilled, by a proposal over the project's period with a location row
     * at its place whose responsible is the project's manager: the preparer of
     * its monthly reports (D-84). No `project_proposal` row is written.
     * A test that needs a template with no proposal detaches it.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (ReportTemplate $template): void {
            if ($template->project_id === null || $template->proposals()->exists()) {
                return;
            }

            $project = Project::query()->findOrFail($template->project_id);

            Activity::withoutRecording(function () use ($template, $project): void {
                // Named after the project, so a legacy report reads as before now that pages name proposals (Y-03 slice 3).
                $proposal = Proposal::factory()->create(array_filter([
                    'name' => $project->name,
                    'planned_date_begin' => $project->start_date?->toDateString(),
                    'planned_date_end' => $project->end_date?->toDateString(),
                ]));

                $row = ProposalLocation::factory()->forProject($project)->create([
                    'proposal_id' => $proposal->id,
                    'city_id' => $template->city_id,
                    'program_id' => $template->program_id,
                    'city_wide' => $template->city_wide,
                ]);

                if ($template->center_id !== null) {
                    $row->centers()->create(['center_id' => $template->center_id]);
                }

                $template->proposals()->attach($proposal->id, ['joined_at' => now()]);
            });
        });
    }

    /**
     * A template of the Y-02 kind: at the place, carried by the proposal,
     * with no legacy project.
     */
    public function forPlace(ReportPlace $place, Proposal $proposal): static
    {
        return $this->state([
            'project_id' => null,
            'city_id' => $place->cityId,
            'program_id' => $place->programId,
            'center_id' => $place->centerId,
            'city_wide' => $place->cityWide,
        ])->afterCreating(fn (ReportTemplate $template) => $template->proposals()->attach($proposal->id, ['joined_at' => now()]));
    }

    /**
     * A template MEAL has confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status' => ReportTemplateStatus::Confirmed,
            'confirmed_at' => now(),
            'confirmed_by' => User::factory(),
        ]);
    }
}
