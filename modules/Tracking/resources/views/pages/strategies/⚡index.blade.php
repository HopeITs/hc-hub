<?php

use Modules\Tracking\Livewire\Concerns\Strategies\DeletesStrategy;
use Modules\Tracking\Livewire\Concerns\Strategies\LinksStrategyPrograms;
use Modules\Tracking\Livewire\Concerns\Strategies\ManagesStrategyForm;
use Modules\Tracking\Models\Strategy;
use App\Support\Like;
use Modules\Tracking\Support\StrategyRollup;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::desk'), Title('titles.strategies')] class extends Component {
    use DeletesStrategy;
    use LinksStrategyPrograms;
    use ManagesStrategyForm;
    use WithPagination;

    /** Past strategies per page. */
    public const int PastPerPage = 15;

    /** The search box appears once there are more strategies than this. */
    public const int SearchFrom = 6;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage('strategiesPage');
    }

    public function filterByStatus(string $status = ''): void
    {
        $this->status = in_array($status, ['active', 'upcoming', 'completed'], true) ? $status : '';
        $this->resetPage('strategiesPage');
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->resetPage('strategiesPage');
    }

    /**
     * What each current strategy adds up to (StrategyRollup), keyed by id.
     * Only the current blocks show figures, so only they are rolled up.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function rollups(): array
    {
        $ids = array_values($this->portfolio['current']->pluck('id')->all());

        return $ids === [] ? [] : StrategyRollup::for(auth()->user(), $ids);
    }

    /**
     * How many programs have a plan for the year that matters, in one call:
     * this year for the current strategies, the first year for upcoming ones.
     * Null when the viewer may not see annual plans.
     *
     * @return array<int, array{planned: int, programs: int, missing: int}>|null
     */
    #[Computed]
    public function readiness(): ?array
    {
        $portfolio = $this->portfolio;
        $yearByStrategy = $portfolio['current']->mapWithKeys(fn (array $strategy): array => [$strategy['id'] => $portfolio['current_year']])
            ->union($portfolio['upcoming']->mapWithKeys(fn (array $strategy): array => [$strategy['id'] => $strategy['start_year']]))
            ->all();

        return $yearByStrategy === [] ? [] : StrategyRollup::planReadiness(auth()->user(), $yearByStrategy);
    }

    /**
     * The strategies split into the page's three sections. Status comes from
     * the years, so each section is ordered in memory (current: newest start
     * first; upcoming: soonest first; past: most recently ended first) and the
     * past list is paginated by hand (.ai/rules/strategies.md).
     *
     * @return array{
     *     current: Collection<int, array{id: int, name: string, url: string, start_year: int, end_year: int, duration_years: int, programs_count: int, pillars_count: int, status: string}>,
     *     upcoming: Collection<int, array{id: int, name: string, url: string, start_year: int, end_year: int, duration_years: int, programs_count: int, pillars_count: int, status: string}>,
     *     past: LengthAwarePaginator<int, array{id: int, name: string, url: string, start_year: int, end_year: int, duration_years: int, programs_count: int, pillars_count: int, status: string}>,
     *     status_counts: array{all: int, active: int, upcoming: int, completed: int},
     *     total_count: int,
     *     last_ended_year: int|null,
     *     current_year: int,
     *     filters_active: bool
     * }
     */
    #[Computed]
    public function portfolio(): array
    {
        $currentYear = now()->year;
        $search = trim($this->search);

        $searchFiltered = Strategy::query()
            ->select(['id', 'slug', 'name', 'start_year', 'end_year'])
            ->withCount(['programs', 'pillars'])
            ->when($search !== '', fn ($query) => $query->where(Like::matches('name', $search)))
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(static fn (Strategy $strategy): array => [
                'id' => $strategy->id,
                'name' => $strategy->name,
                'url' => route('strategies.show', $strategy),
                'start_year' => $strategy->start_year,
                'end_year' => $strategy->end_year,
                'duration_years' => $strategy->end_year - $strategy->start_year + 1,
                'programs_count' => $strategy->programs_count,
                'pillars_count' => $strategy->pillars_count,
                'status' => match (true) {
                    $currentYear < $strategy->start_year => 'upcoming',
                    $currentYear > $strategy->end_year => 'completed',
                    default => 'active',
                },
            ]);

        $shows = fn (string $status): bool => $this->status === '' || $this->status === $status;

        $active = $searchFiltered->where('status', 'active')->sortByDesc('start_year')->values();
        $upcoming = $searchFiltered->where('status', 'upcoming')->sortBy('start_year')->values();
        $completed = $searchFiltered->where('status', 'completed')->sortByDesc('end_year')->values();
        $pastList = $shows('completed') ? $completed : collect();

        $currentPage = $this->getPage('strategiesPage');

        $past = new LengthAwarePaginator(
            $pastList->forPage($currentPage, self::PastPerPage)->values(),
            $pastList->count(),
            self::PastPerPage,
            $currentPage,
            ['pageName' => 'strategiesPage'],
        );

        return [
            'current' => $shows('active') ? $active : collect(),
            'upcoming' => $shows('upcoming') ? $upcoming : collect(),
            'past' => $past,
            'status_counts' => [
                'all' => $searchFiltered->count(),
                'active' => $active->count(),
                'upcoming' => $upcoming->count(),
                'completed' => $completed->count(),
            ],
            'total_count' => $search === '' ? $searchFiltered->count() : Strategy::query()->count(),
            'last_ended_year' => $completed->isEmpty() ? null : (int) $completed->max('end_year'),
            'current_year' => $currentYear,
            'filters_active' => $search !== '' || $this->status !== '',
        ];
    }
};
?>

@php
    $portfolio = $this->portfolio;
    $searchTerm = trim($search);
    $showsCurrent = in_array($status, ['', 'active'], true);
    $noActiveStrategy = $showsCurrent && $searchTerm === '' && $portfolio['current']->isEmpty();
    $nothingToShow = $portfolio['current']->isEmpty() && $portfolio['upcoming']->isEmpty() && $portfolio['past']->total() === 0 && ! $noActiveStrategy;

    $tabs = [
        'all' => ['status' => '', 'label' => __('strategies.tab_all')],
        'active' => ['status' => 'active', 'label' => __('strategies.status_active')],
        'upcoming' => ['status' => 'upcoming', 'label' => __('strategies.upcoming')],
        'completed' => ['status' => 'completed', 'label' => __('strategies.tab_past')],
    ];
    // A tab with nothing in it is not a failed search: say what is missing, with no "reset filters".
    $emptyTab = $searchTerm === '' && $status !== '';
@endphp

<x-desk.register class="admin-directory @container/directory" heading-id="strategies-heading" :title="__('common.strategies')">

    @if ($portfolio['total_count'] > $this::SearchFrom || $searchTerm !== '')
        <x-slot:search>
            <x-desk.register-search id="strategy-search" data-directory-search :label="__('strategies.search_strategies')" :placeholder="__('strategies.search_by_name')" wire:model.live.debounce.300ms="search" />
        </x-slot:search>
    @endif

    @if ($portfolio['total_count'] > 0)
        <x-slot:filters>
            <div class="dk-seg dk-stabs" role="group" aria-label="{{ __('strategies.filter_by_status') }}">
                @foreach ($tabs as $tabKey => $tab)
                    <button type="button" wire:click="filterByStatus('{{ $tab['status'] }}')" aria-pressed="{{ $status === $tab['status'] ? 'true' : 'false' }}" data-test="strategy-tab-{{ $tabKey }}">
                        {{ $tab['label'] }} <span class="dk-num">{{ $portfolio['status_counts'][$tabKey] }}</span>
                    </button>
                @endforeach
            </div>
            <span wire:loading.delay.flex wire:target="search,filterByStatus,resetFilters" class="hidden items-center gap-1.5 text-xs text-secondary-medium dark:text-ink-muted"><flux:icon.arrow-path class="size-3.5 animate-spin" /> {{ __('common.updating') }}</span>
        </x-slot:filters>
    @endif

    @if ($this->canCreateStrategies)
        <x-slot:actions>
            <x-ui.button tone="primary" icon="plus" class="min-h-11 touch-manipulation @sm/directory:min-h-10" wire:click="openCreateModal" data-test="create-strategy-button" data-quick-create>
                {{ __('strategies.new_strategy') }}
            </x-ui.button>
        </x-slot:actions>
    @endif

    @if ($portfolio['total_count'] === 0 || $nothingToShow)
        <x-ui.empty-state
            icon="flag"
            :heading="match (true) {
                $emptyTab => __('strategies.empty_tab.'.$status),
                $portfolio['filters_active'] => __('strategies.no_matching_strategic_periods'),
                default => __('strategies.no_strategic_periods_yet'),
            }"
            :body="match (true) {
                $emptyTab => null,
                $portfolio['filters_active'] => __('common.change_the_search_or_filters_and_try_again'),
                default => __('strategies.strategy_records_will_appear_here_once_planning_cycles_are_d'),
            }"
            data-test="strategies-empty"
        >
            @if ($portfolio['filters_active'] && ! $emptyTab)
                <x-slot:action>
                    <flux:button size="sm" wire:click="resetFilters">{{ __('common.reset_filters') }}</flux:button>
                </x-slot:action>
            @endif
        </x-ui.empty-state>
    @else
        <div class="dk-strategies">
            @if ($showsCurrent && ($portfolio['current']->isNotEmpty() || $noActiveStrategy))
                @include('tracking::pages.strategies._index-current')
            @endif

            @if ($portfolio['upcoming']->isNotEmpty())
                @include('tracking::pages.strategies._index-upcoming')
            @endif

            @if ($portfolio['past']->total() > 0)
                @include('tracking::pages.strategies._index-past')
            @endif
        </div>
    @endif

    @include('tracking::pages.strategies._strategy-form-modal')
    @include('tracking::pages.strategies._programs-modal')
    @include('tracking::pages.strategies._delete-modal')
</x-desk.register>
