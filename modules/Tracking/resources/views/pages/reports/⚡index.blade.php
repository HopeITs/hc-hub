<?php

use App\Support\Like;
use Modules\Tracking\Enums\ReportStatus;
use App\Livewire\Concerns\Desk\PagesRegisterCalendar;
use Modules\Tracking\Livewire\Concerns\FiltersByStrategy;
use Modules\Tracking\Livewire\Concerns\ManagesReportForm;
use Modules\Tracking\Livewire\Concerns\Reports\ReadsReportPortfolio;
use Modules\Tracking\Models\AnnualPlan;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\Report;
use Modules\Core\Models\User;
use App\Notifications\UnreadSubjects;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::desk'), Title('titles.reports')] class extends Component {
    use FiltersByStrategy;
    use ManagesReportForm;
    use PagesRegisterCalendar;
    use ReadsReportPortfolio;
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $seenByMeal = '';

    #[Url(except: '')]
    public string $author = '';

    #[Url(except: '')]
    public string $project = '';

    /**
     * Narrows the feed to one target. Set by the "Record a report" and report
     * trail links on the targets page, so it shows as a removable chip.
     */
    #[Url(except: '')]
    public string $target = '';

    /** '1' narrows the feed to drafts past the date they cover. */
    #[Url(except: '')]
    public string $overdue = '';

    /** attention | progress | approved: the three plain answers at the top of the page. */
    #[Url(except: '')]
    public string $focus = '';

    /**
     * Which shape the report feed renders in: a flat searchable list, or
     * reports grouped by project and indicator to surface the connections
     * between delivery and what has been reported against it.
     */
    #[Url(except: 'list')]
    public string $view = 'list';

    /**
     * `?create=1` (with `?target=`) opens the form already tied to a target —
     * the "Record a report" step from the targets page and the indicator
     * chain. `?edit=<id>` opens a draft for editing.
     */
    public function mount(): void
    {
        if (request()->boolean('create') && Gate::allows('create', Report::class)) {
            $this->openCreateModal($this->target === '' ? null : (int) $this->target);
        }

        $edit = request()->integer('edit');
        $editing = $edit > 0 ? Report::query()->find($edit) : null;

        // A deep link only opens someone else's draft for its author; anyone
        // else lands on the list.
        if ($editing !== null && (! $editing->isEditable() || Gate::allows('update', $editing))) {
            $this->openEditModal($edit);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSeenByMeal(): void
    {
        $this->resetPage();
    }

    public function updatedAuthor(): void
    {
        $this->resetPage();
    }

    public function updatedProject(): void
    {
        $this->resetPage();
    }

    public function setFocus(string $focus): void
    {
        $this->focus = $this->focus === $focus || ! in_array($focus, ['attention', 'progress', 'approved'], true) ? '' : $focus;
        $this->resetPage();
    }

    public function toggleOverdue(): void
    {
        $this->overdue = $this->overdue === '' ? '1' : '';
        $this->resetPage();
    }

    public function clearTarget(): void
    {
        $this->target = '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->seenByMeal = '';
        $this->author = '';
        $this->project = '';
        $this->target = '';
        $this->overdue = '';
        $this->focus = '';
        $this->clearStrategyFilter();
    }

    /**
     * Reports matching the search, author, project and target filters only.
     *
     * Status, MEAL visibility and the overdue chip are deliberately left off
     * this base query: it is shared by the paginated list (which applies them)
     * and the portfolio pulse (which counts every status and both MEAL states
     * side by side, so the strip stays a full breakdown rather than shrinking
     * to whichever status is currently selected).
     *
     * @return Builder<Report>
     */
    private function scopedReports(): Builder
    {
        $search = trim($this->search);

        return Report::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(Like::everyWord($search, fn ($query, string $word) => $query
                    ->where(Like::word('narrative_report_notes', $word))
                    ->orWhereHas('planIndicator.indicator', Like::word('name', $word))
                    ->orWhereHas('planIndicator.project', Like::word('name', $word))));
            })
            ->when($this->author !== '', fn ($query) => $query->where('user_id', (int) $this->author))
            ->when($this->project === 'none', fn ($query) => $query->whereHas('planIndicator', fn ($query) => $query->whereNull('project_id')))
            ->when($this->project !== '' && $this->project !== 'none', fn ($query) => $query
                ->whereHas('planIndicator', fn ($query) => $query->where('project_id', (int) $this->project)))
            ->when($this->target !== '', fn ($query) => $query->where('plan_indicator_id', (int) $this->target))
            ->when($this->strategyFilter !== null, fn ($query) => $query
                ->whereHas('planIndicator', fn ($query) => $this->whereInStrategy($query, 'plan_indicators.project_id')));
    }

    /**
     * scopedReports() with the status, MEAL and overdue filters applied: what
     * the list and the portfolio actually show.
     *
     * @return Builder<Report>
     */
    private function visibleReports(bool $withFocus = true): Builder
    {
        return $this->scopedReports()
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->seenByMeal === 'seen', fn ($query) => $query->where('is_seen_by_meal', true))
            ->when($this->seenByMeal === 'unseen', fn ($query) => $query->where('is_seen_by_meal', false))
            ->when($this->overdue !== '', fn ($query) => $query->overdue())
            ->when($withFocus && $this->focus === 'attention', fn ($query) => $query->where(fn ($query) => $query
                ->where(fn ($late) => $late->overdue())
                ->orWhere('status', ReportStatus::Reviewed->value)))
            ->when($withFocus && $this->focus === 'progress', fn ($query) => $query
                ->where('status', ReportStatus::Draft->value)
                ->whereDate('planned_date', '>=', today()))
            ->when($withFocus && $this->focus === 'approved', fn ($query) => $query->where('status', ReportStatus::Approved->value));
    }

    /**
     * How many reports sit under each headline button. Disjoint and exhaustive
     * (a draft is late or in progress, a reviewed report is waiting, the rest is
     * approved), and read under every filter except the button itself, so a
     * click shows exactly that many.
     *
     * @return array{attention: int, progress: int, approved: int, total: int}
     */
    #[Computed]
    public function focusCounts(): array
    {
        $row = $this->visibleReports(withFocus: false)
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw("coalesce(sum(case when status = 'approved' then 1 else 0 end), 0) as approved")
            ->selectRaw("coalesce(sum(case when status = 'reviewed' or (status = 'draft' and date(planned_date) < ?) then 1 else 0 end), 0) as attention", [today()->toDateString()])
            ->first();

        $total = (int) $row->total;
        $approved = (int) $row->approved;
        $attention = (int) $row->attention;

        return ['attention' => $attention, 'progress' => $total - $approved - $attention, 'approved' => $approved, 'total' => $total];
    }

    /**
     * The target each report is filed against, with everything reported on it
     * — not just the rows on this page — so its achievement is the target's
     * own, by PlanIndicator::achievedValue(), the rule every page shares.
     *
     * @return array<int, mixed>
     */
    private function targetRelations(): array
    {
        return [
            'planIndicator:id,indicator_id,target,period,source_type,source_id,project_id',
            'planIndicator.indicator:id,name,measure,unit',
            'planIndicator.project:id,name',
            'planIndicator.reports:id,plan_indicator_id,status,planned_date,value',
            'planIndicator.source' => fn (MorphTo $morphTo) => $morphTo->morphWith([AnnualPlan::class => [], Proposal::class => []]),
        ];
    }

    /**
     * Records with unread notifications for me, for the row dots.
     *
     * @return list<int>
     */
    #[Computed]
    public function unreadIds(): array
    {
        return app(UnreadSubjects::class)->ids(auth()->user(), 'report');
    }

    /** @return LengthAwarePaginator<int, Report> */
    #[Computed]
    public function reports(): LengthAwarePaginator
    {
        return $this->visibleReports()
            ->select(['id', 'plan_indicator_id', 'user_id', 'status', 'planned_date', 'value', 'submit_date', 'is_seen_by_meal', 'monthly_report_id'])
            ->with([...$this->targetRelations(), 'user:id,email,employee_id', 'monthlyReport:id,slug'])
            ->withCount('files')
            // Late drafts, then reports waiting for approval, then other drafts, then approved.
            ->orderByRaw("case when status = 'draft' and date(planned_date) < ? then 0 when status = 'reviewed' then 1 when status = 'draft' then 2 else 3 end", [today()->toDateString()])
            ->orderByDesc('planned_date')
            ->orderByDesc('id')
            ->paginate(15);
    }

    /**
     * The Board view: a column per status, at most 40 cards each (desk.js).
     *
     * @return list<array{key: string, label: string, count: int, items: Collection<int, Report>}>
     */
    #[Computed]
    public function boardColumns(): array
    {
        $reports = $this->visibleReports()
            ->select(['id', 'slug', 'plan_indicator_id', 'status', 'planned_date'])
            ->with(['planIndicator:id,indicator_id,project_id', 'planIndicator.indicator:id,name', 'planIndicator.project:id,name'])
            ->orderByDesc('planned_date')
            ->get();

        return array_map(function (ReportStatus $status) use ($reports): array {
            $inColumn = $reports->filter(fn (Report $report): bool => $report->status === $status)->values();

            return ['key' => $status->value, 'label' => $status->label(), 'count' => $inColumn->count(), 'items' => $inColumn->take(40)];
        }, ReportStatus::cases());
    }

    /**
     * The Calendar view: each report on its planned date.
     *
     * @return list<array{date: string, label: string, href: string}>
     */
    #[Computed]
    public function calendarItems(): array
    {
        $start = $this->calendarMonthStart();

        return $this->visibleReports()
            ->select(['id', 'slug', 'plan_indicator_id', 'planned_date'])
            ->with(['planIndicator:id,indicator_id', 'planIndicator.indicator:id,name'])
            ->whereBetween('planned_date', [$start->toDateString(), $start->endOfMonth()->toDateString()])
            ->get()
            ->map(fn (Report $report): array => [
                'date' => $report->planned_date->toDateString(),
                'label' => $report->planIndicator->indicator->name,
                'href' => route('reports.show', $report),
            ])
            ->values()
            ->all();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function authorOptions(): Collection
    {
        return User::query()->select(['id', 'email'])->orderByName()->get();
    }

    /** @return Collection<int, Project> */
    #[Computed]
    public function projectOptions(): Collection
    {
        return Project::query()
            ->select(['id', 'name'])
            ->whereIn('id', PlanIndicator::query()->select('project_id')->whereNotNull('project_id'))
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    protected function reportSaved(Report $report, bool $isCreate): void
    {
        $this->resetPage();
    }

    protected function reportDeleted(): void
    {
        $this->resetPage();
    }
};
?>

@php
    $filtersAreActive = $search !== '' || $status !== '' || $seenByMeal !== '' || $author !== '' || $project !== '' || $target !== '' || $overdue !== '' || $focus !== '' || $this->strategyFilter !== null;
    $focusCounts = $this->focusCounts;
    $insights = ['total' => $focusCounts['total']];

    // A badly missed target is 'critical' (the brand red), not 'warn': this
    // app's tone vocabulary already reserves red for exactly that signal
    // (see ui/stat-tile, ui/progress-ring), and a missed target is the one
    // thing here a projects/fundraising manager most needs to see at a glance.
    $achievementTone = fn (?int $value): string => match (true) {
        $value === null => 'neutral',
        $value >= 90 => 'good',
        $value < 50 => 'critical',
        default => 'neutral',
    };

    $achievementTextClass = fn (?int $value): string => match ($achievementTone($value)) {
        'good' => 'text-status-good',
        'critical' => 'text-primary dark:text-brand-text',
        default => 'text-secondary-dark dark:text-ink',
    };

    $achievementBarClass = fn (?int $value): string => match ($achievementTone($value)) {
        'good' => 'bg-status-good',
        'critical' => 'bg-primary dark:bg-status-critical',
        default => 'bg-secondary-dark dark:bg-ink-muted',
    };

    $achievementBadgeClass = fn (?int $value): string => match ($achievementTone($value)) {
        'good' => 'bg-status-good-soft text-status-good',
        'critical' => 'bg-status-critical-soft text-primary dark:text-brand-text',
        default => 'bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink',
    };

    // Where the target was committed: the plan year or the proposal's name, not just its kind.
    $sourceLabel = fn (Modules\Tracking\Models\PlanIndicator $planIndicator): string => match (true) {
        $planIndicator->source instanceof Modules\Tracking\Models\AnnualPlan => __('common.fy_year', ['year' => $planIndicator->source->year]),
        $planIndicator->source instanceof Modules\Tracking\Models\Proposal => $planIndicator->source->name,
        default => __('targets.source_no_longer_available'),
    };

    $formatFigure = fn (float $value): string => number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2);
    $unitOf = fn (Modules\Tracking\Models\PlanIndicator $planIndicator): string => $planIndicator->indicator->measure === Modules\Tracking\Enums\IndicatorMeasure::Percentage ? '%' : '';

    // A tiny inline sparkline: values normalised into a fixed 100x28 box, no
    // axes or chrome. Fewer than two points has no line to draw.
    $sparklinePoints = function (array $values): ?string {
        if (count($values) < 2) {
            return null;
        }

        $min = min($values);
        $max = max($values);
        $range = $max - $min;
        $step = 100 / (count($values) - 1);

        return collect($values)
            ->map(fn (float $value, int $index): string => round($index * $step, 1).','.round($range > 0 ? 28 - (($value - $min) / $range) * 28 : 14, 1))
            ->implode(' ');
    };

@endphp

<x-desk.register
    class="@container/directory"
    heading-id="report-directory-heading"
    :title="__('common.reports')"
    :count="$insights['total']"
    :view="$view"
    :views="[
        'list' => ['icon' => 'table', 'label' => __('desk.register.table')],
        'board' => ['icon' => 'board', 'label' => __('desk.register.board')],
        'calendar' => ['icon' => 'cal', 'label' => __('desk.register.calendar')],
        'portfolio' => ['icon' => 'grid', 'label' => __('reports.portfolio_view')],
    ]"
>
    <x-slot:actions>
            @can('create', Modules\Tracking\Models\Report::class)
                <button type="button" class="dk-btn dk-primary" wire:click="openCreateModal" data-test="create-report-button" data-quick-create><x-desk.icon name="plus" />{{ __('reports.record_report') }}</button>
            @endcan
        </x-slot:actions>
    <x-slot:search>
        <x-desk.register-search :label="__('reports.search_reports')" :placeholder="__('reports.indicator_or_narrative')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>
    <x-slot:filters>
            <x-desk.strategy-chip :strategy="$this->strategyFilter" />

            <x-ui.header-filter-select :label="__('common.project')" wire:model.live="project" :value="$project">
                <option value="">{{ __('targets.any_project') }}</option>
                <option value="none">{{ __('common.organisation_wide') }}</option>
                @foreach ($this->projectOptions as $projectOption)
                    <option value="{{ $projectOption->id }}">{{ $projectOption->name }}</option>
                @endforeach
            </x-ui.header-filter-select>

            <div class="contents" x-data="{ open: @js($author !== '' || $seenByMeal !== '') }">
                <button type="button" class="dk-btn" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" data-test="more-filters-button">
                    <flux:icon.adjustments-horizontal variant="micro" class="size-4" />
                    {{ __('reports.more_filters') }}
                </button>

                <div class="contents" x-show="open" x-cloak>
                    <x-ui.header-filter-select :label="__('reports.reported_by')" wire:model.live="author" :value="$author">
                        <option value="">{{ __('common.anyone') }}</option>
                        @foreach ($this->authorOptions as $authorOption)
                            <option value="{{ $authorOption->id }}">{{ $authorOption->name }}</option>
                        @endforeach
                    </x-ui.header-filter-select>

                    <x-ui.header-filter-select :label="__('reports.meal_review')" wire:model.live="seenByMeal" :value="$seenByMeal">
                        <option value="">{{ __('reports.any') }}</option>
                        <option value="seen">{{ __('reports.seen_by_meal') }}</option>
                        <option value="unseen">{{ __('reports.not_yet_seen') }}</option>
                    </x-ui.header-filter-select>
                </div>
            </div>
        </x-slot:filters>

    @include('tracking::pages.reports._index-focus-bar')


    @if ($this->scopedTargetLabel !== null)
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="clearTarget" class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-secondary-dark bg-secondary-dark px-3 py-1 text-xs font-semibold text-white hover:opacity-90 dark:border-ink dark:bg-ink dark:text-surface-0" data-test="target-scope-chip">
                <flux:icon.flag variant="micro" class="size-3.5 shrink-0" aria-hidden="true" />
                <span class="truncate">{{ $this->scopedTargetLabel }}</span>
                <flux:icon.x-mark variant="micro" class="size-3.5 shrink-0" aria-hidden="true" />
                <span class="sr-only">{{ __('common.reset_filters') }}</span>
            </button>
            @can('create', Modules\Tracking\Models\Report::class)
                <x-ui.button size="sm" icon="plus" wire:click="openCreateModal({{ (int) $target }})">{{ __('reports.record_another_for_this_target') }}</x-ui.button>
            @endcan
        </div>
    @endif

    @if ($view === 'portfolio')
        @include('tracking::pages.reports._index-portfolio')
    @elseif ($view === 'board')
        @include('tracking::pages.reports._index-board')
    @elseif ($view === 'calendar')
        <x-desk.month-calendar :month="$this->calendarMonthStart()->toDateString()" :items="$this->calendarItems" />
    @else
        @include('tracking::pages.reports._index-list')
    @endif

    <x-tracking::reports.form-modal
        :open="$showReportModal"
        :editing="$editingReportId !== null"
        :target-options="$showReportModal ? $this->targetOptions : []"
        :form-target="$showReportModal ? $this->formTarget : null"
        :window="$showReportModal ? $this->formWindow() : null"
        :ratio="$showReportModal ? $this->formRatio() : null"
        :ratio-preview="$showReportModal ? $this->ratioPreview() : null"
    />
</x-desk.register>
