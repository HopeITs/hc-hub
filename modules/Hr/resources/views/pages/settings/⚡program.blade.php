<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Livewire\Concerns\Settings\ListsAssignedEmployees;
use Modules\Hr\Support\Settings\HrAccess;
use Modules\Hr\Support\Settings\OrgCounts;

/*
 * One program as HR sees it: its details, where it runs (positions and
 * headcount per governorate and per center) and who works on it now.
 * Read-only: programs are created and edited from Tracking.
 */
new #[Layout('layouts::desk'), Title('hr_settings.titles.program')] class extends Component {
    use ListsAssignedEmployees;
    use WithPagination;

    #[Locked]
    public int $programId;

    public function mount(Program $program): void
    {
        Gate::authorize('programs.view');

        $this->programId = $program->id;
    }

    protected function assignmentScope(): array
    {
        return ['program_id' => $this->programId];
    }

    #[Computed]
    public function program(): Program
    {
        return Program::query()
            ->select(['id', 'name', 'code', 'slug', 'description_en', 'description_ar', 'start_date', 'end_date', 'is_active', 'manager_id'])
            ->with('manager:id,first_name_en,last_name_en,first_name_ar,last_name_ar')
            ->findOrFail($this->programId);
    }

    /** @return list<int>|null */
    private function cityIds(): ?array
    {
        return HrAccess::for(Auth::user())->cityIds();
    }

    /** @return array{positions: int, seats: int, employees: int, cities: int, centers: int} */
    #[Computed]
    public function totals(): array
    {
        $counts = OrgCounts::positions('program_id', $this->cityIds(), $this->assignmentScope())->get($this->programId);

        return [
            'positions' => $counts->positions ?? 0,
            'seats' => $counts->seats ?? 0,
            'employees' => OrgCounts::headcount($this->cityIds(), $this->assignmentScope()),
            'cities' => $counts->cities ?? 0,
            'centers' => $counts->centers ?? 0,
        ];
    }

    /**
     * Positions and headcount per governorate and per center.
     *
     * @return array{cities: list<array{id: int, name: string, positions: int, employees: int}>, centers: list<array{id: int, name: string, city: string, positions: int, employees: int}>}
     */
    #[Computed]
    public function breakdown(): array
    {
        $scope = $this->assignmentScope();
        $cityIds = $this->cityIds();

        $build = function (string $column, callable $row) use ($scope, $cityIds): array {
            $employees = OrgCounts::employees($column, $cityIds, $scope);

            return OrgCounts::positions($column, $cityIds, $scope)
                ->map(fn (object $counts, int $id): array => $row($id) + ['id' => $id, 'positions' => $counts->positions, 'employees' => $employees->get($id, 0)])
                ->sortBy('name')
                ->values()
                ->all();
        };

        return [
            'cities' => $build('city_id', fn (int $id): array => ['name' => City::nameOf($id)]),
            'centers' => $build('center_id', fn (int $id): array => [
                'name' => Center::nameOf($id),
                'city' => City::nameOf(Center::remembered($id)?->city_id),
            ]),
        ];
    }
};
?>

@php
    $program = $this->program;
    $totals = $this->totals;
    $breakdown = $this->breakdown;
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $description = trim((string) (app()->getLocale() === 'ar' ? ($program->description_ar ?: $program->description_en) : ($program->description_en ?: $program->description_ar)));
    $canOpenCenters = auth()->user()->can('centers.view');
    $dates = collect([$program->start_date, $program->end_date])->map(fn ($date) => $date?->translatedFormat(__('formats.date')) ?? '…');
@endphp

<section class="flex flex-col gap-5" data-hr-program="{{ $program->id }}">
    <x-ui.detail-header
        :back="route('hr.settings.programs.index')"
        :breadcrumbs="[['label' => __('hr_settings.titles.programs'), 'url' => route('hr.settings.programs.index')], ['label' => $program->name]]"
        :title="$program->name"
    >
        <x-slot:meta>
            @if ($program->is_active)
                <span class="inline-flex items-center rounded-md border border-status-good/30 bg-status-good-soft px-2 py-0.5 text-xs font-semibold text-status-good">{{ __('common.active') }}</span>
            @else
                <span class="inline-flex items-center rounded-md border border-secondary-light px-2 py-0.5 text-xs font-semibold text-secondary-dark dark:border-line-strong dark:text-ink">{{ __('hr_settings.inactive') }}</span>
            @endif
            <span>{{ __('hr_settings.fields.code') }}: <bdi dir="ltr" class="font-mono">{{ trim((string) $program->code) ?: '—' }}</bdi></span>
            <span>{{ __('hr_settings.fields.slug') }}: <bdi dir="ltr" class="font-mono">{{ $program->slug }}</bdi></span>
            @if ($program->start_date !== null || $program->end_date !== null)
                <span>{{ $dates[0] }} – {{ $dates[1] }}</span>
            @endif
            <span>{{ __('hr_settings.programs.manager') }}: <bdi>{{ $program->manager?->name ?? __('common.not_assigned') }}</bdi></span>
        </x-slot:meta>
    </x-ui.detail-header>

    <p class="type-meta {{ $muted }}">{{ __('hr_settings.programs.read_only_note') }}</p>

    @if ($description !== '')
        <p class="max-w-3xl text-sm leading-relaxed text-secondary-dark dark:text-ink" dir="auto">{{ $description }}</p>
    @endif

    <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
        <x-ui.stat-tile :label="__('hr_settings.cities.positions')" :value="$totals['positions']" />
        <x-ui.stat-tile :label="__('hr_settings.programs.seats')" :value="$totals['seats']" />
        <x-ui.stat-tile :label="__('hr_settings.cities.employees')" :value="$totals['employees']" tone="good" />
        <x-ui.stat-tile :label="__('common.cities')" :value="$totals['cities']" />
        <x-ui.stat-tile :label="__('hr_settings.cities.centers')" :value="$totals['centers']" />
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <x-ui.panel :title="__('hr_settings.programs.by_city')" :count="count($breakdown['cities'])">
            @if ($breakdown['cities'] === [])
                <x-ui.empty-state icon="map-pin" :heading="__('hr_settings.programs.no_positions')" class="min-h-40!" />
            @else
                <ul class="divide-y divide-panel-border">
                    @foreach ($breakdown['cities'] as $row)
                        <li wire:key="program-city-{{ $row['id'] }}" class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                            <bdi class="min-w-0 truncate font-medium">{{ $row['name'] }}</bdi>
                            <span class="shrink-0 text-xs tabular-nums {{ $muted }}">{{ trans_choice('hr_settings.count_positions', $row['positions'], ['count' => $row['positions']]) }} · {{ trans_choice('hr_settings.count_employees', $row['employees'], ['count' => $row['employees']]) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>

        <x-ui.panel :title="__('hr_settings.programs.by_center')" :count="count($breakdown['centers'])">
            @if ($breakdown['centers'] === [])
                <x-ui.empty-state icon="building-office-2" :heading="__('hr_settings.programs.no_positions')" class="min-h-40!" />
            @else
                <ul class="divide-y divide-panel-border">
                    @foreach ($breakdown['centers'] as $row)
                        <li wire:key="program-center-{{ $row['id'] }}" class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                            <span class="min-w-0">
                                @if ($canOpenCenters)
                                    <a href="{{ route('hr.settings.centers.show', $row['id']) }}" wire:navigate class="font-medium underline decoration-secondary-light underline-offset-2 hover:decoration-current"><bdi>{{ $row['name'] }}</bdi></a>
                                @else
                                    <bdi class="font-medium">{{ $row['name'] }}</bdi>
                                @endif
                                <span class="block text-xs {{ $muted }}"><bdi>{{ $row['city'] }}</bdi></span>
                            </span>
                            <span class="shrink-0 text-xs tabular-nums {{ $muted }}">{{ trans_choice('hr_settings.count_positions', $row['positions'], ['count' => $row['positions']]) }} · {{ trans_choice('hr_settings.count_employees', $row['employees'], ['count' => $row['employees']]) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>
    </div>

    @include('hr::pages.settings._assignments', ['show' => 'center'])
</section>
