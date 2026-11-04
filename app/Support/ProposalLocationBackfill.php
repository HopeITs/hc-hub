<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Proposal;
use Illuminate\Support\Facades\DB;

/**
 * Give every existing proposal–project link its location row (W-34.md §6):
 * city, program, center and owner from the project, `is_new = false`,
 * `project_id` set. The one definition behind `proposals:backfill-locations`
 * and the seeder.
 *
 * A link whose project already has a row is left alone, so a re-run writes
 * nothing. Two linked projects on the same city and program of one proposal
 * (two centers) share one row: the second is "merged" into it as a center.
 * Proposals with no link get no row; they fail the `locations` key on their
 * next transition, which is intended. Records no activity and dispatches
 * nothing: this is history, not news.
 */
final class ProposalLocationBackfill
{
    public function __construct(private ProposalLocationRows $rows) {}

    /**
     * Run the backfill, writing only when $apply is true; a dry run does the
     * same work inside a transaction it rolls back, so its counts are exact.
     *
     * @return array{links: int, already: int, inserted: int, merged: int, proposals_without_links: int, multi_city_proposals: int}
     */
    public function run(bool $apply): array
    {
        $links = DB::table('project_proposal')
            ->join('projects', 'projects.id', '=', 'project_proposal.project_id')
            ->orderBy('project_proposal.proposal_id')
            ->orderBy('project_proposal.project_id')
            ->get(['project_proposal.proposal_id', 'project_proposal.project_id', 'projects.city_id']);

        $byProposal = $links->groupBy('proposal_id');

        $counts = [
            'links' => $links->count(),
            'already' => 0,
            'inserted' => 0,
            'merged' => 0,
            'proposals_without_links' => Proposal::query()->whereNotIn('id', $byProposal->keys()->all())->count(),
            'multi_city_proposals' => $byProposal->filter(fn ($group): bool => $group->pluck('city_id')->unique()->count() > 1)->count(),
        ];

        DB::beginTransaction();

        try {
            Activity::withoutRecording(function () use ($byProposal, &$counts): void {
                foreach ($byProposal as $proposalId => $group) {
                    $locked = Proposal::query()->whereKey($proposalId)->lockForUpdate()->firstOrFail();
                    $projects = Project::query()
                        ->whereKey($group->pluck('project_id')->all())
                        ->orderBy('id')
                        ->get(['id', 'city_id', 'center_id', 'host_center_id', 'program_id', 'user_id']);

                    foreach ($projects as $project) {
                        if ($locked->locations()->where('project_id', $project->id)->exists()) {
                            $counts['already']++;

                            continue;
                        }

                        $result = $this->rows->attachProject($locked, $project);
                        $counts['inserted'] += $result['added'];
                        $counts['merged'] += $result['changed'];
                    }
                }
            });

            $apply ? DB::commit() : DB::rollBack();
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        return $counts;
    }
}
