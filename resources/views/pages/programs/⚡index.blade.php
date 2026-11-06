<?php

use App\Enums\ProjectStatus;
use App\Support\Like;
use App\Models\HrEmployee;
use App\Models\Program;
use App\Models\Strategy;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::desk'), Title('titles.programs')] class extends Component {
    /** The strategy / manager filter value that lists programs with nobody linked. */
    private const Unassigned = 'none';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $strategy = '';

    #[Url(except: '')]
    public string $manager = '';

    #[Url(except: '')]
    public string $gap = '';

    /** attention | setup | ontrack: the three plain answers at the top of the page. */
    #[Url(except: '')]
    public string $focus = '';

    public int $visibleProgramCount = 8;

    public bool $showProgramModal = false;
    #[Locked]
    public ?int $editingProgramId = null;
    public string $editingProgramName = '';
    public string $strategyId = '';
    public string $managerId = '';
    #[Locked]
    public string $originalStrategyId = '';
    #[Locked]
    public string $originalManagerId = '';

    public function updatedSearch(): void
    {
        $this->resetVisiblePrograms();
    }

    public function updatedStrategy(): void
    {
        $this->resetVisiblePrograms();
    }

    public function updatedManager(): void
    {
        $this->resetVisiblePrograms();
    }

    public function filterByStrategy(?int $strategyId = null): void
    {
        $this->strategy = $strategyId === null ? '' : (string) $strategyId;
        $this->resetVisiblePrograms();
    }

    public function filterByManager(?int $managerId = null): void
    {
        $this->manager = $managerId === null ? '' : (string) $managerId;
        $this->resetVisiblePrograms();
    }

    public function updatedGap(): void
    {
        $this->gap = in_array($this->gap, ['no_strategy', 'no_manager', 'no_plan'], true) ? $this->gap : '';
        $this->resetVisiblePrograms();
    }

    public function setFocus(string $focus): void
    {
        $this->focus = $this->focus === $focus || ! in_array($focus, ['attention', 'setup', 'ontrack'], true) ? '' : $focus;
        $this->resetVisiblePrograms();
    }

    public function clearFilters(): void
    {
        $this->focus = '';
        $this->search = '';
        $this->strategy = '';
        $this->manager = '';
        $this->gap = '';
        $this->resetVisiblePrograms();
    }

    public function showGap(string $gap): void
    {
        $this->gap = $gap;
        $this->resetVisiblePrograms();
    }

    public function loadMore(): void
    {
        if ($this->visibleProgramCount < $this->programs->count()) {
            $this->visibleProgramCount += 4;
        }
    }

    protected function resetVisiblePrograms(): void
    {
        $this->visibleProgramCount = 8;
    }

    /**
     * Every program matching the search and filters, each with its status and
     * the reasons behind it, most urgent first and then by name.
     *
     * Programs are few, so the status is worked out here in one pass instead of
     * in SQL; the tab counts and the list then cannot disagree.
     *
     * @return Collection<int, Program>
     */
    #[Computed]
    public function allPrograms(): Collection
    {
        $counts = [
            'projects',
            'annualPlans',
            'projects as overdue_projects_count' => fn (Builder $query) => $query
                ->where('status', ProjectStatus::Active->value)
                ->whereDate('end_date', '<', today()),
            'projects as risky_projects_count' => fn (Builder $query) => $query
                ->whereHas('risks', fn (Builder $risks) => $risks->where('happened', true)),
        ];

        if ($this->canSeeFunding()) {
            $counts['projects as unfunded_projects_count'] = fn (Builder $query) => $query->doesntHave('proposals');
        }

        $rank = ['attention' => 0, 'setup' => 1, 'ontrack' => 2];

        return $this->filteredProgramsQuery()
            ->select(['id', 'strategy_id', 'manager_id', 'updated_at'])
            ->with([
                'strategy:id,name,start_year,end_year',
                'manager:id,first_name_en,last_name_en,first_name_ar,last_name_ar',
            ])
            ->withCount($counts)
            ->orderByName()
            ->get()
            ->each(function (Program $program): void {
                $late = (int) $program->overdue_projects_count + (int) $program->risky_projects_count;
                $unfunded = (int) ($program->unfunded_projects_count ?? 0);

                $program->setAttribute('health', $program->projects_count === 0 ? 'none' : ($late > 0 ? 'critical' : ($unfunded > 0 ? 'warn' : 'good')));
                $program->setAttribute('status', match (true) {
                    $late > 0 || $unfunded > 0 => 'attention',
                    $program->strategy_id === null || $program->manager_id === null || $program->projects_count === 0 => 'setup',
                    default => 'ontrack',
                });
                $program->setAttribute('urgency', $late);
            })
            ->sortBy([
                fn (Program $a, Program $b): int => $rank[$a->status] <=> $rank[$b->status],
                fn (Program $a, Program $b): int => $b->urgency <=> $a->urgency,
            ])
            ->values();
    }

    /**
     * How many programs sit under each tab. Disjoint, and read from the same
     * list the rows come from, so a click shows exactly that many.
     *
     * @return array{attention: int, setup: int, ontrack: int, total: int}
     */
    #[Computed]
    public function focusCounts(): array
    {
        $byStatus = $this->allPrograms->countBy('status');

        return [
            'attention' => (int) $byStatus->get('attention', 0),
            'setup' => (int) $byStatus->get('setup', 0),
            'ontrack' => (int) $byStatus->get('ontrack', 0),
            'total' => $this->allPrograms->count(),
        ];
    }

    /** @return Collection<int, Program> */
    #[Computed]
    public function programs(): Collection
    {
        return $this->focus === ''
            ? $this->allPrograms
            : $this->allPrograms->where('status', $this->focus)->values();
    }

    /** @return Builder<Program> */
    protected function filteredProgramsQuery(): Builder
    {
        $search = trim($this->search);

        return Program::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereNameLike($search)
                        ->orWhereHas('strategy', fn (Builder $query) => $query->where('name', 'like', Like::contains($search)))
                        ->orWhereIn('manager_id', HrEmployee::idsMatchingName($search));
                });
            })
            ->when($this->strategy === self::Unassigned, fn (Builder $query) => $query->whereNull('strategy_id'))
            ->when($this->strategy !== '' && $this->strategy !== self::Unassigned, fn (Builder $query) => $query->where('strategy_id', (int) $this->strategy))
            ->when($this->manager === self::Unassigned, fn (Builder $query) => $query->whereNull('manager_id'))
            ->when($this->manager !== '' && $this->manager !== self::Unassigned, fn (Builder $query) => $query->where('manager_id', (int) $this->manager))
            ->when($this->gap === 'no_strategy', fn (Builder $query) => $query->whereNull('strategy_id'))
            ->when($this->gap === 'no_manager', fn (Builder $query) => $query->whereNull('manager_id'))
            ->when($this->gap === 'no_plan', fn (Builder $query) => $query->whereDoesntHave(
                'annualPlans',
                fn (Builder $plans) => $plans->where('year', now()->year)->whereHas('planIndicators'),
            ));
    }

    /** @return Collection<int, Strategy> */
    #[Computed]
    public function strategyOptions(): Collection
    {
        return Strategy::query()
            ->select(['id', 'name', 'start_year', 'end_year'])
            ->orderByDesc('start_year')
            ->orderByDesc('end_year')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * Who manages a program today, with how many, so the filter lists only
     * people who can match something. A manager already in the URL stays
     * listed even if no program has them any more.
     *
     * @return list<array{value: string, label: string}>
     */
    #[Computed]
    public function managerOptions(): array
    {
        $counts = Program::query()
            ->whereNotNull('manager_id')
            ->selectRaw('manager_id, count(*) as total')
            ->groupBy('manager_id')
            ->pluck('total', 'manager_id');

        if (ctype_digit($this->manager) && ! $counts->has((int) $this->manager)) {
            $counts->put((int) $this->manager, 0);
        }

        $labels = HrEmployee::pickerLabels()->pluck('label', 'value');

        return $counts
            ->map(fn (int|string $total, int|string $id): array => [
                'value' => (string) $id,
                // The left-to-right mark keeps "(2)" after an Arabic name instead of reordering before it.
                'label' => ($labels->get((string) $id) ?? HrEmployee::query()->find($id)?->name ?? '#'.$id)."\u{200E} (".$total.')',
            ])
            ->sortBy(fn (array $option): string => mb_strtolower($option['label']))
            ->values()
            ->all();
    }

    /** @return array{without_strategy: int, without_manager: int} */
    #[Computed]
    public function unassignedCounts(): array
    {
        /** @var object{without_strategy: int|string|null, without_manager: int|string|null} $row */
        $row = Program::query()
            ->selectRaw('sum(case when strategy_id is null then 1 else 0 end) as without_strategy')
            ->selectRaw('sum(case when manager_id is null then 1 else 0 end) as without_manager')
            ->first();

        return ['without_strategy' => (int) $row->without_strategy, 'without_manager' => (int) $row->without_manager];
    }

    /**
     * A strategy as one short name: the years are already in names like
     * "Country Strategy 2026-2030", so they are added only when the name lacks them.
     */
    public function strategyLabel(Strategy $strategy): string
    {
        return str_contains($strategy->name, (string) $strategy->start_year)
            ? $strategy->name
            : $strategy->name.' ('.$strategy->start_year.'–'.$strategy->end_year.')';
    }

    /** Whether the viewer may see funding signals (projects with no proposal yet). */
    protected function canSeeFunding(): bool
    {
        return auth()->user()?->can('proposals.view') === true;
    }

    public function openEditModal(int $programId): void
    {
        $program = Program::query()
            ->select(['id', 'strategy_id', 'manager_id'])
            ->findOrFail($programId);

        Gate::authorize('update', $program);

        $this->resetValidation();
        $this->editingProgramId = $program->id;
        $this->editingProgramName = $program->name;
        $this->strategyId = $this->originalStrategyId = (string) $program->strategy_id;
        $this->managerId = $this->originalManagerId = (string) $program->manager_id;
        $this->showProgramModal = true;
    }

    /**
     * Link an HR program to a strategy and manager.
     *
     * The program itself (its name, whether it exists) belongs to HR, so only
     * the two tracking-side links are editable, and either may be left empty.
     */
    public function saveProgram(): void
    {
        Gate::authorize('update', Program::query()->findOrFail($this->editingProgramId));

        $validated = $this->validate([
            'strategyId' => ['nullable', 'integer', Rule::exists(Strategy::class, 'id')],
            'managerId' => ['nullable', 'integer', Rule::exists(HrEmployee::class, 'id')],
        ], [
            'strategyId.exists' => __('programs.the_selected_strategy_is_no_longer_available'),
            'managerId.exists' => __('programs.the_selected_manager_is_no_longer_available'),
        ]);

        Program::query()->findOrFail($this->editingProgramId)->update([
            'strategy_id' => blank($validated['strategyId']) ? null : (int) $validated['strategyId'],
            'manager_id' => blank($validated['managerId']) ? null : (int) $validated['managerId'],
        ]);

        $this->showProgramModal = false;
        $this->resetProgramForm();

        Flux::toast(variant: 'success', text: __('programs.program_assignment_updated'));
    }

    public function closeProgramModal(): void
    {
        $this->showProgramModal = false;
        $this->resetProgramForm();
    }

    protected function resetProgramForm(): void
    {
        $this->resetValidation();
        $this->editingProgramId = null;
        $this->editingProgramName = '';
        $this->strategyId = '';
        $this->managerId = '';
        $this->originalStrategyId = '';
        $this->originalManagerId = '';
    }
};
?>

@php
    $filtersAreActive = $search !== '' || $strategy !== '' || $manager !== '' || $gap !== '' || $focus !== '';
    $focusCounts = $this->focusCounts;
    $totalShown = $this->programs->count();
    $visiblePrograms = $this->programs->take($visibleProgramCount);
    $hasMorePrograms = $totalShown > $visibleProgramCount;
@endphp

<x-desk.register class="admin-directory @container/directory" heading-id="program-directory-heading" :title="__('common.programs')" :count="$focusCounts['total']">
    <x-slot:search>
        <x-desk.register-search id="program-search" data-directory-search :label="__('programs.search_programs')" :placeholder="__('programs.program_strategy_or_manager')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>
    <x-slot:filters>
        <x-ui.header-filter-select id="program-strategy" :label="__('common.strategy')" :search-label="__('programs.search_strategies')" wire:model.live="strategy" :value="$strategy">
            <option value="">{{ __('programs.all_strategies') }}</option>
            <option value="none">{{ __('programs.filter_not_assigned', ['count' => $this->unassignedCounts['without_strategy']]) }}</option>
            @foreach ($this->strategyOptions as $strategyOption)
                <option value="{{ $strategyOption->id }}">{{ $this->strategyLabel($strategyOption) }}</option>
            @endforeach
        </x-ui.header-filter-select>

        <div class="contents" x-data="{ open: @js($manager !== '') }">
            <button type="button" class="dk-btn" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" data-test="more-filters-button">
                <flux:icon.adjustments-horizontal variant="micro" class="size-4" />
                {{ __('programs.more_filters') }}
            </button>

            <div class="contents" x-show="open" x-cloak>
                <x-ui.header-filter-select id="program-manager" :label="__('programs.manager')" :search-label="__('programs.search_managers')" wire:model.live="manager" :value="$manager">
                    <option value="">{{ __('common.all_managers') }}</option>
                    <option value="none">{{ __('programs.filter_not_assigned', ['count' => $this->unassignedCounts['without_manager']]) }}</option>
                    @foreach ($this->managerOptions as $managerOption)
                        <option value="{{ $managerOption['value'] }}">{{ $managerOption['label'] }}</option>
                    @endforeach
                </x-ui.header-filter-select>
            </div>
        </div>

        @php
            $gapLabel = match ($gap) {
                'no_plan' => __('programs.gap_no_plan', ['year' => now()->year]),
                'no_strategy' => __('programs.gap_no_strategy'),
                'no_manager' => __('programs.gap_no_manager'),
                default => null,
            };
        @endphp
        @if ($gapLabel !== null)
            <span class="dk-chip dk-gapchip" data-gap-filter-chip>
                <span>{{ $gapLabel }}</span>
                <button type="button" wire:click="$set('gap', '')" aria-label="{{ __('programs.clear_gap_named', ['name' => $gapLabel]) }}" data-test="clear-gap-filter">{{ __('programs.clear_gap') }}<x-desk.icon name="x" /></button>
            </span>
        @endif
    </x-slot:filters>

    @include('pages.programs._index-focus-bar')

    @if ($visiblePrograms->isEmpty())
        <x-ui.empty-state icon="briefcase" :heading="$filtersAreActive ? __('programs.no_matching_programs') : __('programs.no_programs_from_hr_yet')" :body="$filtersAreActive ? __('common.change_the_search_or_filters_and_try_again') : __('programs.programs_appear_here_once_they_are_added_in_the_hr_system')">
            @if ($filtersAreActive)
                <x-slot:action>
                    <x-ui.button size="sm" wire:click="clearFilters">{{ __('common.reset_filters') }}</x-ui.button>
                </x-slot:action>
            @endif
        </x-ui.empty-state>
    @else
        @include('pages.programs._index-list')
        <p class="text-center text-xs text-secondary-medium dark:text-ink-muted" data-program-shown>{{ __('programs.showing_count', ['shown' => $visiblePrograms->count(), 'total' => $totalShown]) }}</p>
        @if ($hasMorePrograms)
            <div class="flex justify-center pt-1"><x-ui.button tone="primary" icon="plus" class="min-h-11 px-5 data-loading:opacity-70" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">{{ __('programs.load_more') }}</x-ui.button></div>
        @endif
    @endif

    <x-ui.modal
        wire:model="showProgramModal"
        size="md"
        :title="__('programs.assign_name', ['name' => $editingProgramName])"
        :description="__('programs.the_program_name_is_managed_in_the_hr_system_link_it_to_a_st')"
        submit="saveProgram"
        close="closeProgramModal"
        :discard-guard="$showProgramModal ? ['fields' => ['strategyId', 'managerId'], 'original' => ['strategyId' => $originalStrategyId, 'managerId' => $originalManagerId]] : null"
    >
        @if ($showProgramModal)
            <div class="grid gap-y-4">
                <x-ui.form-select wire:model="strategyId" :label="__('programs.linked_strategy')" autofocus>
                    <option value="">{{ __('common.not_assigned') }}</option>
                    @foreach ($this->strategyOptions as $strategyOption)
                        <option value="{{ $strategyOption->id }}">{{ $this->strategyLabel($strategyOption) }}</option>
                    @endforeach
                </x-ui.form-select>

                <x-ui.form-select wire:model="managerId" :label="__('programs.direct_manager')" :help="__('programs.the_assigned_manager_is_the_hr_employee_directly_accountable')" employees>
                    <option value="">{{ __('common.not_assigned') }}</option>
                </x-ui.form-select>
            </div>
        @endif

        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="save-program-button">{{ __('common.save_changes') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>
</x-desk.register>
