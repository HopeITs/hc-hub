<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\Proposals;

use App\Models\HrCity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Tracking\Actions\Projects\SaveProject;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalLocation;
use Modules\Tracking\Support\MaterialisationResult;

/**
 * Give every location row of a proposal its project (contracts.md §6, W-34a
 * slice 2, D-62). ApproveProposal calls it inside its own transaction, after
 * the proposal row is locked and before the linked projects are approved.
 *
 * A row that carries a project is skipped, so a second run writes nothing.
 * A row without a program is left waiting (its pending request already
 * blocks the approval through the `locations_complete` key, so here it is
 * only reported). Every other row yields one project per center: the open
 * project already at that center and program is linked instead of created
 * (warning `location_linked_existing`); otherwise a draft is created only
 * through SaveProject and linked only through LinkProposalProject, which
 * writes the project back on the row. A row with several centers keeps the
 * first project's id and the others join it as centers, as the link wrapper
 * already does for a second project on one city and program.
 *
 * The owner of a created draft is the row's responsible when they manage a
 * program (SaveProject admits only program managers as owners), else the
 * manager of the row's program; with neither, the approval is refused
 * before anything is written. Drafts start without sectors: the owner adds
 * them on the project form. Nothing here writes a status.
 */
final class MaterialiseProposalLocations
{
    public const WarningLinkedExisting = 'location_linked_existing';

    public function __construct(private SaveProject $saveProject, private LinkProposalProject $linkProject) {}

    public function handle(Proposal $proposal, int $actorId): MaterialisationResult
    {
        $result = DB::transaction(function () use ($proposal, $actorId): MaterialisationResult {
            $locked = Proposal::query()->whereKey($proposal->getKey())->lockForUpdate()->firstOrFail();

            Gate::authorize('approve', $locked);

            $actor = User::query()->findOrFail($actorId);
            ['steps' => $steps, 'waiting' => $waiting] = $this->walk($locked);
            $owners = [];

            foreach ($steps as $index => $step) {
                if ($step['open'] === null) {
                    $owners[$index] = $this->ownerFor($step['row']);
                }
            }

            if ($owners !== []) {
                Gate::forUser($actor)->authorize('create', Project::class);
            }

            $created = [];
            $linked = [];

            foreach ($steps as $index => $step) {
                if ($step['open'] !== null) {
                    $this->linkProject->handle($locked, $step['open']->id);
                    $linked[] = $step['open']->id;

                    continue;
                }

                $project = $this->saveProject->handle(null, [
                    'centerId' => $step['center_id'],
                    'programId' => $step['row']->program_id,
                    'userId' => $owners[$index],
                ], $actor);

                $this->linkProject->handle($locked, $project->id);
                $created[] = $project->id;
            }

            return new MaterialisationResult($created, $linked, $waiting, $linked === [] ? [] : [self::WarningLinkedExisting]);
        });

        $proposal->unsetRelation('projects');
        $proposal->unsetRelation('locations');

        return $result;
    }

    /**
     * What handle() would do, for the approve modal: the row id of each
     * project to create, the open projects to reuse, the rows left waiting.
     * Reads only.
     */
    public function preview(Proposal $proposal): MaterialisationResult
    {
        Gate::authorize('view', $proposal);

        ['steps' => $steps, 'waiting' => $waiting] = $this->walk($proposal);
        $created = [];
        $linked = [];

        foreach ($steps as $step) {
            if ($step['open'] === null) {
                $created[] = $step['row']->id;
            } else {
                $linked[] = $step['open']->id;
            }
        }

        return new MaterialisationResult($created, $linked, $waiting, $linked === [] ? [] : [self::WarningLinkedExisting]);
    }

    /**
     * One step per project the rows still need, in row order then center
     * order, each with the open project that already holds its place.
     *
     * @return array{steps: list<array{row: ProposalLocation, center_id: int|null, open: Project|null}>, waiting: list<int>}
     */
    private function walk(Proposal $proposal): array
    {
        $steps = [];
        $waiting = [];

        /** @var ProposalLocation $row */
        foreach ($proposal->locations()->with('centers')->orderBy('sort')->orderBy('id')->get() as $row) {
            if ($row->project_id !== null) {
                continue;
            }

            if ($row->program_id === null) {
                $waiting[] = $row->id;

                continue;
            }

            $centerIds = $row->centerIds();

            foreach ($centerIds === [] ? [null] : $centerIds as $centerId) {
                $steps[] = [
                    'row' => $row,
                    'center_id' => $centerId,
                    'open' => Project::openProjectFor($centerId, $row->city_id, $row->program_id),
                ];
            }
        }

        return ['steps' => $steps, 'waiting' => $waiting];
    }

    /**
     * The HR employee who will own the row's project: the responsible when
     * they manage a program, else the manager of the row's program.
     *
     * @throws ValidationException under `locations_complete` when neither exists
     */
    private function ownerFor(ProposalLocation $row): int
    {
        $responsible = $row->responsible_employee_id;

        if ($responsible !== null && Program::query()->where('manager_id', $responsible)->exists()) {
            return $responsible;
        }

        $manager = Program::query()->whereKey($row->program_id)->value('manager_id');

        if ($manager !== null) {
            return (int) $manager;
        }

        throw ValidationException::withMessages([
            'locations_complete' => __('proposals.location_owner_required', ['city' => HrCity::query()->find($row->city_id)?->name ?? '#'.$row->city_id]),
        ]);
    }
}
