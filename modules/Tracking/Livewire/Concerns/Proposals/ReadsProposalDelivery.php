<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Proposals;

use App\Support\EmployeeProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Tracking\Enums\ReportStatus;
use Modules\Tracking\Models\DonorProposal;
use Modules\Tracking\Models\DonorReport;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalLocation;
use Modules\Tracking\Models\Report;
use Modules\Tracking\Models\Task;
use Modules\Tracking\Support\ProposalLocationRows;

/**
 * The delivery on proposals/show: the funded projects, the tasks and
 * targets behind them, and the reports owed on both sides.
 *
 * Used by proposals/show only. The page provides proposalId and the
 * #[Computed] proposal(); agreements comes from ReadsProposalFunding.
 *
 * @property int $proposalId
 * @property-read Proposal $proposal  #[Computed] proposal()
 * @property-read Collection<int, DonorProposal> $agreements  #[Computed] agreements()
 * @property-read Collection<int, Project> $projects  #[Computed] projects()
 * @property-read list<array{id: int, city: string, program: string|null, responsible: string|null, centers: list<string>, fieldWork: bool, status: string, project: string|null}> $locationRows  #[Computed] locationRows()
 * @property-read Collection<int, PlanIndicator> $targets  #[Computed] targets()
 */
trait ReadsProposalDelivery
{
    /**
     * The Locations chapter (Y-01): one entry per location row in `sort`
     * order, with its place and a single status. The status comes from the
     * one definition of `locations_complete` (ProposalLocationRows): a row it
     * does not list is `complete`, a listed one says why, program first. A
     * city-wide row (D-88) shows field work in place of centers and is never
     * `missing_center`. Names come from the Core and HR rows, loaded once.
     *
     * @return list<array{id: int, city: string, program: string|null, responsible: string|null, centers: list<string>, fieldWork: bool, status: string, project: string|null}>
     */
    #[Computed]
    public function locationRows(): array
    {
        $rows = $this->proposal->locations()
            ->with(['centers', 'program', 'project:id,name'])
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $incomplete = array_flip(ProposalLocationRows::incompleteRowIds($this->proposal));
        $cities = City::query()->whereKey($rows->pluck('city_id')->unique()->all())->get()->keyBy('id');
        $employees = EmployeeProfile::namesByIds($rows->pluck('responsible_employee_id')->filter()->unique()->values()->all());

        return array_values($rows->map(function (ProposalLocation $row) use ($incomplete, $cities, $employees): array {
            $status = match (true) {
                ! isset($incomplete[$row->id]) => 'complete',
                $row->program_id === null => 'missing_program',
                $row->responsible_employee_id === null => 'missing_responsible',
                default => 'missing_center',
            };

            return [
                'id' => $row->id,
                'city' => $cities->get($row->city_id)?->name ?? __('common.unknown_city'),
                'program' => $row->program?->name,
                'responsible' => $row->responsible_employee_id === null ? null : ($employees[$row->responsible_employee_id] ?? '#'.$row->responsible_employee_id),
                'centers' => $row->city_wide ? [] : array_map(fn (int $centerId): string => Center::remembered($centerId)?->name ?? '#'.$centerId, $row->centerIds()),
                'fieldWork' => $row->city_wide,
                'status' => $status,
                'project' => $row->project?->name,
            ];
        })->all());
    }

    /**
     * The projects this proposal funds.
     *
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return $this->proposal->projects()
            ->select(['projects.id', 'projects.slug', 'projects.name', 'projects.city_id', 'projects.program_id', 'projects.status', 'projects.start_date', 'projects.end_date'])
            ->with(['city:id,name_en,name_ar', 'program:id'])
            ->withCount('risks')
            ->orderBy('projects.name')
            ->get();
    }

    /**
     * Tasks delivering this proposal, carrying the project they reach through.
     *
     * A task has one source: either this proposal directly, or one of the
     * proposal's funded projects. Tasks sourced straight from the proposal
     * are a real state and get their own group rather than being dropped.
     *
     * @return Collection<int, Task>
     */
    #[Computed]
    public function tasks(): Collection
    {
        $fundedProjectIds = $this->projects->modelKeys();

        return Task::query()
            ->select(['id', 'slug', 'source_type', 'source_id', 'parent_task_id', 'department_id', 'kind', 'media_kinds', 'delivery_state', 'title', 'status', 'priority', 'start_date', 'due_date', 'progress_percentage'])
            ->with(['taskAssignees.employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar', 'source'])
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->where('source_type', 'proposal')->where('source_id', $this->proposalId))
                ->orWhere(fn (Builder $query) => $query->where('source_type', 'project')->whereIn('source_id', $fundedProjectIds)))
            ->orderByRaw('due_date is null, due_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Targets committed in this proposal, with the reports filed against them.
     *
     * Filtered on the morph map key 'proposal', never a class name. The source
     * is hydrated with the proposal's projects so chainIssues() can answer
     * whether a target was committed against a project this proposal does not
     * fund — without that relation it would query once per target.
     *
     * @return Collection<int, PlanIndicator>
     */
    #[Computed]
    public function targets(): Collection
    {
        $targets = PlanIndicator::query()
            ->select(['id', 'source_type', 'source_id', 'indicator_id', 'target', 'project_id', 'period'])
            ->where('source_type', 'proposal')
            ->where('source_id', $this->proposalId)
            ->with([
                'indicator:id,name,result_id,project_id,measure,unit',
                'indicator.result:id,statement,type,program_id,proposal_id',
                'project:id,name,program_id',
                'reports:id,plan_indicator_id,status,value,planned_date,period_start,period_end,submit_date',
            ])
            ->get();

        $source = $this->proposal->setRelation('projects', $this->projects);
        $targets->each(fn (PlanIndicator $target) => $target->setRelation('source', $source));

        return $targets;
    }

    /**
     * Every report filed against a target committed in this proposal — what
     * the donor is owed, newest due date first.
     *
     * Read off the targets already loaded, so the tab costs no extra query.
     * Each report carries its target so the row can name the indicator.
     *
     * @return \Illuminate\Support\Collection<int, Report>
     */
    #[Computed]
    public function reports(): \Illuminate\Support\Collection
    {
        return $this->targets
            ->flatMap(fn (PlanIndicator $target) => $target->reports->each(fn (Report $report) => $report->setRelation('planIndicator', $target)))
            ->sortBy([
                fn (Report $a, Report $b): int => $b->planned_date <=> $a->planned_date,
                fn (Report $a, Report $b): int => $b->id <=> $a->id,
            ])
            ->values();
    }

    /**
     * The reports owed to donors under this proposal's agreements, carrying
     * their agreement and donor so a row can name who is owed.
     *
     * @return Collection<int, DonorReport>
     */
    #[Computed]
    public function donorReports(): Collection
    {
        if ($this->agreements->isEmpty()) {
            return new Collection;
        }

        return DonorReport::query()
            ->select(['id', 'donor_proposal_id', 'title', 'type', 'due_on', 'submitted_on'])
            ->whereIn('donor_proposal_id', $this->agreements->modelKeys())
            ->orderBy('due_on')
            ->orderBy('id')
            ->get()
            ->each(fn (DonorReport $report) => $report->setRelation('donorProposal', $this->agreements->find($report->donor_proposal_id)));
    }

    /**
     * Whether a report is still owed: drafted but never submitted for review.
     */
    protected function reportIsOwed(Report $report): bool
    {
        return $report->status === ReportStatus::Draft && $report->submit_date === null;
    }
}
