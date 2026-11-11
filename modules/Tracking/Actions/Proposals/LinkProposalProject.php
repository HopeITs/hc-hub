<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Proposals;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Tracking\Actions\Projects\ReopenProjectAsDraft;
use Modules\Tracking\Enums\ProjectStatus;
use Modules\Tracking\Events\ProposalLocationsChanged;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Support\ProposalLocationRows;

/**
 * Link a project to a proposal, from either the proposal or the project page.
 *
 * The project is loaded through Project::linkableToProposals(), the same
 * query every picker uses, so an archived, owner-rejected, missing or
 * tampered id is refused with one message. A closed project is reopened as
 * a draft in the same transaction. Linking a project already on the
 * proposal changes nothing, its beneficiaries included. A payroll proposal
 * (W-32) never takes a project.
 *
 * Since W-34a a link is also a location row (contracts.md §7): the row
 * waiting on the project's city and program is filled, else one is added
 * with the project's placement, and ProposalLocationsChanged fires after
 * commit. Kept for one release behind the rows, then removed.
 *
 * Pivot writes fire no model events, so the link is recorded explicitly,
 * with the project's name and never an amount.
 */
final class LinkProposalProject
{
    public function handle(Proposal $proposal, int $projectId, ?int $targetBeneficiaries = null): void
    {
        DB::transaction(function () use ($proposal, $projectId, $targetBeneficiaries): void {
            $locked = Proposal::query()->whereKey($proposal->getKey())->lockForUpdate()->firstOrFail();

            Gate::authorize('update', $locked);

            if ($locked->isPayroll()) {
                throw ValidationException::withMessages(['projectIds' => __('proposals.payroll_has_no_projects')]);
            }

            if ($targetBeneficiaries !== null && $targetBeneficiaries < 1) {
                throw ValidationException::withMessages(['beneficiaries' => __('proposals.enter_at_least_one_beneficiary')]);
            }

            $project = Project::query()->linkableToProposals()->whereKey($projectId)->lockForUpdate()->first();

            if ($project === null) {
                throw ValidationException::withMessages(['projectIds' => __('proposals.that_project_is_no_longer_available')]);
            }

            Gate::authorize('view', $project);

            if ($locked->projects()->whereKey($project->id)->exists()) {
                return;
            }

            if ($project->status === ProjectStatus::Closed) {
                app(ReopenProjectAsDraft::class)->handle($project);
            }

            $locked->projects()->attach($project->id, ['target_beneficiaries' => $targetBeneficiaries]);

            $locked->recordActivity('proposal.project_linked', properties: [
                'project_id' => $project->id,
                'project' => $project->name,
            ]);

            $counts = app(ProposalLocationRows::class)->attachProject($locked, $project);

            if ($counts['added'] + $counts['changed'] > 0) {
                $locked->recordActivity('proposal.locations_changed', [], $counts);
                ProposalLocationsChanged::dispatch($locked->id, auth()->user()?->id, $counts['added'], $counts['removed'], $counts['changed']);
            }
        });

        $proposal->unsetRelation('projects');
        $proposal->unsetRelation('locations');
    }
}
