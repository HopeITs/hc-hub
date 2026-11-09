<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Programs;

use App\Enums\IndicatorMeasure;
use App\Enums\ReportStatus;
use App\Enums\ResultType;
use App\Models\AnnualPlan;
use App\Models\PlanIndicator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * The program page's Annual plans tab: a year strip, filter cards over the
 * picked year's targets, and a grid of group cards that open to show
 * their targets with progress and reporting state.
 *
 * Reads the page's annualPlans() (ReadsProgramDelivery) and
 * program(). Targets are read in one query for the picked plan only.
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AnnualPlan> $annualPlans */
trait ManagesProgramPlans
{
    /** The tab's filters, in the order the stat cards show them; the first is the default. */
    public const PLAN_FILTERS = ['all', 'reached', 'behind', 'late', 'review'];

    /** The ways the tab can group a plan's targets; the first is the default. */
    public const PLAN_GROUPINGS = ['project', 'level', 'status', 'indicator'];

    /** A target's reporting state, most urgent first. */
    public const PLAN_STATUSES = ['late', 'review', 'draft', 'none', 'approved'];

    /**
     * Percentage points a target may trail the share of the year gone before
     * it counts as behind pace.
     */
    public const PLAN_PACE_GRACE = 10;

    /** The fiscal year picked in the strip; 0 lets the tab pick one. */
    #[Url(as: 'year', except: 0)]
    public int $planYear = 0;

    #[Url(as: 'targets', except: 'all')]
    public string $planFilter = 'all';

    #[Url(as: 'by', except: 'project')]
    public string $planGroupBy = 'project';

    public string $planSearch = '';

    public function pickPlanYear(int $year): void
    {
        $this->planYear = $year;
    }

    /**
     * Pick a filter, or go back to every target when the picked one is
     * already on.
     */
    public function pickPlanFilter(string $filter): void
    {
        $filter = in_array($filter, self::PLAN_FILTERS, true) ? $filter : 'all';
        $this->planFilter = $this->planFilter === $filter ? 'all' : $filter;
    }

    public function updatedPlanFilter(): void
    {
        $this->planFilter = in_array($this->planFilter, self::PLAN_FILTERS, true) ? $this->planFilter : 'all';
    }

    public function updatedPlanGroupBy(): void
    {
        $this->planGroupBy = in_array($this->planGroupBy, self::PLAN_GROUPINGS, true) ? $this->planGroupBy : 'project';
    }

    /**
     * The year strip: last year, this year and next year only, each with its
     * plan (or null for a gap) and how many targets the plan holds.
     *
     * @return list<array{year: int, plan: AnnualPlan|null, targets: int}>
     */
    #[Computed]
    public function planStripYears(): array
    {
        $currentYear = CarbonImmutable::today()->year;
        $plans = $this->annualPlans->keyBy(fn (AnnualPlan $plan): int => (int) $plan->year);

        return array_map(function (int $year) use ($plans): array {
            $plan = $plans->get($year);

            return ['year' => $year, 'plan' => $plan, 'targets' => (int) ($plan?->plan_indicators_count ?? 0)];
        }, [$currentYear - 1, $currentYear, $currentYear + 1]);
    }

    /** The year the tab shows: the picked one when the strip has it, else the current year. */
    #[Computed]
    public function shownPlanYear(): int
    {
        $years = array_column($this->planStripYears, 'year');

        return in_array($this->planYear, $years, true) ? $this->planYear : CarbonImmutable::today()->year;
    }

    /** The plan written for the shown year, or null for a gap. */
    #[Computed]
    public function shownPlan(): ?AnnualPlan
    {
        return $this->annualPlans->firstWhere('year', $this->shownPlanYear);
    }

    /**
     * How much of a fiscal year has gone, 0–100: all of a past year, none of
     * a future one.
     */
    public function planYearElapsed(int $year): int
    {
        $today = CarbonImmutable::today();

        return match (true) {
            $year < $today->year => 100,
            $year > $today->year => 0,
            default => (int) round($today->dayOfYear / $today->daysInYear * 100),
        };
    }

    /**
     * The shown plan's targets, one array each with its progress and its
     * reporting state. Null when the viewer may not see targets; empty for a
     * year with no plan.
     *
     * "Late" is a draft report whose planned date has passed (nobody has
     * submitted it); "review" is a submitted report waiting for approval.
     *
     * @return Collection<int, array<string, mixed>>|null
     */
    #[Computed]
    public function planTargetRows(): ?Collection
    {
        if (auth()->user()?->can('targets.view') !== true) {
            return null;
        }

        $plan = $this->shownPlan;

        if ($plan === null) {
            return collect();
        }

        $today = CarbonImmutable::today();
        $elapsed = $this->planYearElapsed((int) $plan->year);
        $draft = ReportStatus::Draft->value;

        return PlanIndicator::query()
            ->where('source_type', 'annual_plan')
            ->where('source_id', $plan->id)
            ->select(['id', 'indicator_id', 'target', 'project_id'])
            ->selectRaw(PlanIndicator::achievedValueSql().' as achieved')
            ->selectRaw('(select count(*) from reports r where r.plan_indicator_id = plan_indicators.id and r.status = ?) as approved_count', [ReportStatus::Approved->value])
            ->selectRaw('(select count(*) from reports r where r.plan_indicator_id = plan_indicators.id and r.status = ?) as review_count', [ReportStatus::Reviewed->value])
            ->selectRaw('(select count(*) from reports r where r.plan_indicator_id = plan_indicators.id and r.status = ?) as draft_count', [$draft])
            ->selectRaw('(select count(*) from reports r where r.plan_indicator_id = plan_indicators.id and r.status = ? and r.planned_date < ?) as late_count', [$draft, $today->toDateString()])
            ->selectRaw('(select min(r.planned_date) from reports r where r.plan_indicator_id = plan_indicators.id and r.status = ?) as next_date', [$draft])
            ->with([
                'indicator:id,name,measure,unit,result_id',
                'indicator.result:id,statement,type',
                'project:id,name,city_id',
                'project.city:id,name_en,name_ar',
            ])
            ->get()
            ->map(function (PlanIndicator $target) use ($elapsed, $today): array {
                $goal = (float) $target->target;
                $achieved = (float) $target->getAttribute('achieved');
                $percent = $goal > 0 ? $achieved / $goal * 100 : null;
                $reached = $goal > 0 ? $achieved >= $goal : $achieved > 0;
                $late = (int) $target->getAttribute('late_count');
                $review = (int) $target->getAttribute('review_count');
                $drafts = (int) $target->getAttribute('draft_count');
                $approved = (int) $target->getAttribute('approved_count');
                $nextDate = $target->getAttribute('next_date');
                $next = $nextDate === null ? null : CarbonImmutable::parse($nextDate)->startOfDay();
                $indicator = $target->indicator;

                return [
                    'id' => $target->id,
                    'indicator_id' => $target->indicator_id,
                    'name' => $indicator?->name ?? __('programs.plans_indicator_removed'),
                    'is_percent' => $indicator?->measure === IndicatorMeasure::Percentage,
                    'unit' => $indicator?->measure === IndicatorMeasure::Percentage || blank($indicator?->unit) ? null : $indicator->unit,
                    'result' => $indicator?->result?->statement,
                    'level' => $indicator?->result?->type,
                    'project_id' => $target->project_id,
                    'project' => $target->project?->name,
                    'city' => $target->project?->city?->name,
                    'target' => $goal,
                    'achieved' => $achieved,
                    'percent' => $percent,
                    'reached' => $reached,
                    'behind' => ! $reached && $percent !== null && $percent + self::PLAN_PACE_GRACE < $elapsed,
                    'late' => $late,
                    'review' => $review,
                    'drafts' => $drafts,
                    'approved' => $approved,
                    'next' => $next,
                    'late_days' => $next !== null && $next->lt($today) ? (int) $next->diffInDays($today) : 0,
                    'status' => match (true) {
                        $late > 0 => 'late',
                        $review > 0 => 'review',
                        $drafts > 0 => 'draft',
                        $approved === 0 => 'none',
                        default => 'approved',
                    },
                ];
            })
            ->values();
    }

    /** Whether a target row passes a filter (the picked one by default). */
    public function planTargetPassesFilter(array $row, ?string $filter = null): bool
    {
        return match ($filter ?? $this->planFilter) {
            'reached' => $row['reached'],
            'behind' => $row['behind'],
            'late' => $row['late'] > 0,
            'review' => $row['review'] > 0,
            default => true,
        };
    }

    /**
     * Everything the tab draws: the filter counts and the group cards with
     * their targets. Null when the viewer may not see targets.
     *
     * @return array{counts: array<string, int>, total: int, searching: bool, shown: int, groups: Collection<int, array<string, mixed>>, progress: int|null}|null
     */
    #[Computed]
    public function planBoard(): ?array
    {
        $targets = $this->planTargetRows;

        if ($targets === null) {
            return null;
        }

        $counts = collect(self::PLAN_FILTERS)
            ->mapWithKeys(fn (string $filter): array => [$filter => $targets->filter(fn (array $row): bool => $this->planTargetPassesFilter($row, $filter))->count()])
            ->all();

        $needle = mb_strtolower(trim($this->planSearch));
        $searching = $needle !== '';
        $matching = $searching
            ? $targets->filter(fn (array $row): bool => collect([$row['name'], $row['project'], $row['result']])->filter()->contains(fn (string $text): bool => str_contains(mb_strtolower($text), $needle)))
            : $targets->filter(fn (array $row): bool => $this->planTargetPassesFilter($row));

        // Whatever needs a hand first: late reports, then behind pace, then the least done.
        $rows = $matching->sortBy([
            fn (array $a, array $b): int => ($b['late'] > 0) <=> ($a['late'] > 0),
            fn (array $a, array $b): int => $b['behind'] <=> $a['behind'],
            fn (array $a, array $b): int => $a['reached'] <=> $b['reached'],
            fn (array $a, array $b): int => ($a['percent'] ?? 0) <=> ($b['percent'] ?? 0),
            fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']),
        ])->values();

        return [
            'counts' => $counts,
            'total' => $targets->count(),
            'searching' => $searching,
            'shown' => $rows->count(),
            'groups' => $this->planBuckets($rows),
            'progress' => $this->planBucketFor('all', __('programs.plans_all_targets'), $targets)['progress'],
        ];
    }

    /**
     * The group cards for the current grouping, each holding the targets that
     * pass the filter. Status and level keep their own fixed order; projects
     * and indicators put the ones with late reports first, then the biggest.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    protected function planBuckets(Collection $rows): Collection
    {
        return match ($this->planGroupBy) {
            'status' => collect(self::PLAN_STATUSES)
                ->map(fn (string $status): array => $this->planBucketFor($status, __('programs.plans_status_'.$status), $rows->where('status', $status)))
                ->filter(fn (array $bucket): bool => $bucket['count'] > 0)
                ->values(),
            'level' => collect([...ResultType::cases(), null])
                ->map(fn (?ResultType $type): array => $this->planBucketFor(
                    $type?->value ?? 'unlevelled',
                    $type?->label() ?? __('programs.plans_no_level'),
                    $rows->filter(fn (array $row): bool => $row['level'] === $type),
                    level: $type,
                ))
                ->filter(fn (array $bucket): bool => $bucket['count'] > 0)
                ->values(),
            'indicator' => $rows->groupBy('indicator_id')
                ->map(fn (Collection $group, int|string $indicatorId): array => $this->planBucketFor('indicator-'.$indicatorId, $group->first()['name'], $group->values()))
                ->sortBy([
                    fn (array $a, array $b): int => $b['late'] <=> $a['late'],
                    fn (array $a, array $b): int => $b['count'] <=> $a['count'],
                    fn (array $a, array $b): int => strnatcasecmp($a['label'], $b['label']),
                ])
                ->values(),
            default => $this->planProjectBuckets($rows),
        };
    }

    /**
     * One group per project, with the program-wide targets last. Projects
     * usually repeat their program's name, so the cards drop it.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    protected function planProjectBuckets(Collection $rows): Collection
    {
        $programPrefix = mb_strtolower(trim((string) $this->program->name)).' ';

        $projects = $rows->whereNotNull('project_id')->groupBy('project_id')
            ->map(function (Collection $group, int|string $projectId) use ($programPrefix): array {
                $name = (string) $group->first()['project'];
                $short = $programPrefix !== ' ' && str_starts_with(mb_strtolower($name), $programPrefix) ? trim(mb_substr($name, mb_strlen($programPrefix))) : '';

                return $this->planBucketFor('project-'.$projectId, $name, $group->values(), short: $short !== '' ? $short : null, href: route('projects.show', (int) $projectId));
            })
            ->sortBy([
                fn (array $a, array $b): int => $b['late'] <=> $a['late'],
                fn (array $a, array $b): int => $b['count'] <=> $a['count'],
                fn (array $a, array $b): int => strnatcasecmp($a['label'], $b['label']),
            ])
            ->values();

        $programWide = $rows->whereNull('project_id')->values();

        return $programWide->isEmpty()
            ? $projects
            : $projects->push($this->planBucketFor('program', __('programs.plans_program_wide'), $programWide));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{key: string, label: string, short: string|null, href: string|null, level: ResultType|null, rows: Collection<int, array<string, mixed>>, count: int, late: int, reached: int, progress: int|null, next: CarbonImmutable|null, city: string|null, projects: int}
     */
    protected function planBucketFor(string $key, string $label, Collection $rows, ?string $short = null, ?string $href = null, ?ResultType $level = null): array
    {
        $rated = $rows->whereNotNull('percent');

        return [
            'key' => $key,
            'label' => $label,
            'short' => $short,
            'href' => $href,
            'level' => $level,
            'rows' => $rows->values(),
            'count' => $rows->count(),
            'late' => $rows->filter(fn (array $row): bool => $row['late'] > 0)->count(),
            'reached' => $rows->where('reached', true)->count(),
            'progress' => $rated->isEmpty() ? null : (int) round($rated->avg(fn (array $row): float => min(100, (float) $row['percent']))),
            'next' => $rows->pluck('next')->filter()->sort()->first(),
            'city' => $rows->pluck('city')->filter()->unique()->count() === 1 ? $rows->pluck('city')->filter()->first() : null,
            'projects' => $rows->pluck('project_id')->filter()->unique()->count(),
        ];
    }

    /** A target figure as people read it: "1,250", "12.5", "80%". */
    public function formatPlanFigure(float $value, bool $isPercent): string
    {
        $formatted = rtrim(rtrim(number_format($value, 1), '0'), '.');

        return $isPercent ? $formatted.'%' : $formatted;
    }
}
