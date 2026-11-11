<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Targets;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Tracking\Models\AnnualPlan;
use Modules\Tracking\Models\Indicator;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\Report;

/**
 * The visible targets: one page of results' targets with their sources and
 * figures, and the report trail opened under one of them.
 *
 * Used by targets/index only. targets() reads matchingTargets() and
 * resultPage from FiltersTargets; donorRelation() reads canViewAgreements()
 * from ChecksFinanceVisibility.
 *
 * @property-read LengthAwarePaginator<int, Indicator> $resultPage  the concrete paginator paginate() returns (its Collection mixin gives pluck())
 */
trait ReadsTargets
{
    #[Locked]
    public ?int $expandedTargetId = null;

    /**
     * The proposal relation that lists its donors: with each budget for someone
     * who may see agreements, otherwise names only so no amount is ever loaded.
     */
    protected function donorRelation(): string
    {
        return $this->canViewAgreements() ? 'donors' : 'donorNames';
    }

    /** @return Collection<int, PlanIndicator> */
    #[Computed]
    public function targets(): Collection
    {
        $resultIds = $this->resultPage->pluck('result_id')->all();

        return $this->matchingTargets()
            ->select(['id', 'source_type', 'source_id', 'indicator_id', 'target', 'project_id', 'period', 'updated_at'])
            ->selectRaw(PlanIndicator::achievedValueSql().' as actual_value')
            ->selectRaw(PlanIndicator::pendingValueSql().' as pending_value')
            ->selectRaw("(select count(*) from reports r where r.plan_indicator_id = plan_indicators.id and r.status = 'reviewed') as reviewed_count")
            ->selectRaw("(select count(*) from reports r where r.plan_indicator_id = plan_indicators.id and r.status = 'draft') as draft_count")
            ->whereIn('indicator_id', Indicator::query()->select('id')->whereIn('result_id', $resultIds))
            ->with([
                // morphTo resolves to AnnualPlan or Proposal — or to null, when
                // the source was removed by a write that fired no model events.
                'source' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                    AnnualPlan::class => ['program:id'],
                    // projects: which projects the proposal funds, for chainIssues().
                    Proposal::class => [$this->donorRelation().':id,name', 'projects:id'],
                ]),
                'indicator:id,name,result_id,project_id,measure,unit,baseline',
                'indicator.result:id,statement,type,program_id,proposal_id',
                'project:id,name,city_id,program_id',
                'project.city:id,name_en,name_ar',
            ])
            ->withCount('reports')
            // Keep a result's indicators together so the page reads as groups.
            ->orderBy(Indicator::query()->select('result_id')->whereColumn('indicators.id', 'plan_indicators.indicator_id'))
            // Inside a result, what needs a hand comes first: nothing approved, then in progress, then achieved.
            ->orderByRaw('case when '.PlanIndicator::achievementStatusSql('not_started').' then 0 when '.PlanIndicator::achievementStatusSql('in_progress').' then 1 else 2 end')
            ->orderBy('indicator_id')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * The reports behind the expanded target, newest period first.
     *
     * @return Collection<int, Report>
     */
    #[Computed]
    public function expandedReports(): Collection
    {
        if ($this->expandedTargetId === null) {
            return new Collection;
        }

        return Report::query()
            ->select(['id', 'plan_indicator_id', 'user_id', 'status', 'period_start', 'period_end', 'planned_date', 'value', 'is_seen_by_meal'])
            ->with('user:id,email')
            ->withCount('files')
            ->where('plan_indicator_id', $this->expandedTargetId)
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Open or close the report trail under one target.
     */
    public function toggleReports(int $targetId): void
    {
        $this->expandedTargetId = $this->expandedTargetId === $targetId ? null : $targetId;
    }
}
