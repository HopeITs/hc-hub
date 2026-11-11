<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Proposals;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Tracking\Events\ProposalLocationsChanged;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Support\ProposalLocationRows;

/**
 * Unlink a project from a proposal.
 *
 * The project is loaded only through the proposal's own links, so a project
 * the proposal does not fund is "not found". The proposal row is locked and
 * its links recounted, so two people never unlink the last two projects at
 * once. A project carrying targets of this proposal that already have
 * reports stays linked; targets without reports stay too and are flagged on
 * the targets tab.
 *
 * Since W-34a the project's location row goes with the link (contracts.md
 * §7), and ProposalLocationsChanged fires after commit.
 */
final class UnlinkProposalProject
{
    public function handle(Proposal $proposal, int $projectId): void
    {
        DB::transaction(function () use ($proposal, $projectId): void {
            $locked = Proposal::query()->whereKey($proposal->getKey())->lockForUpdate()->firstOrFail();

            Gate::authorize('update', $locked);

            $project = $locked->projects()->whereKey($projectId)->firstOrFail(['projects.id', 'projects.name']);

            if ($locked->projects()->count() <= 1) {
                throw ValidationException::withMessages(['project' => __('proposals.the_last_linked_project_cannot_be_unlinked')]);
            }

            $hasReportedTargets = PlanIndicator::query()
                ->where('source_type', $locked->getMorphClass())
                ->where('source_id', $locked->id)
                ->where('project_id', $project->id)
                ->has('reports')
                ->exists();

            if ($hasReportedTargets) {
                throw ValidationException::withMessages(['project' => __('proposals.this_project_has_reported_targets_in_this_proposal')]);
            }

            $locked->projects()->detach($project->id);

            $locked->recordActivity('proposal.project_unlinked', properties: [
                'project_id' => $project->id,
                'project' => $project->name,
            ]);

            $counts = app(ProposalLocationRows::class)->detachProject($locked, $project->id);

            if ($counts['removed'] > 0) {
                $locked->recordActivity('proposal.locations_changed', [], $counts);
                ProposalLocationsChanged::dispatch($locked->id, auth()->user()?->id, $counts['added'], $counts['removed'], $counts['changed']);
            }
        });

        $proposal->unsetRelation('projects');
        $proposal->unsetRelation('locations');
    }
}
