<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Support\Desk\HrStatistics;
use Modules\Hr\Support\Desk\HrViewer;

/**
 * HR statistics (the old Statistics page): head counts for the governorates
 * this person looks after — totals, employees by department and job level,
 * running contracts by type, and who has never asked for annual leave.
 * Salary figures of the old page are left out on purpose. Every number is
 * remembered ten minutes (HrStatistics).
 */
new #[Layout('layouts::desk'), Title('hr_desk.statistics.title')] class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()->isAdministrator() || auth()->user()->can('statistics.view'), 403);
    }

    /**
     * @return list<int>|null
     */
    #[Computed]
    public function scope(): ?array
    {
        return HrViewer::for(auth()->user())->cityScope();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function figures(): array
    {
        $stats = app(HrStatistics::class);
        $scope = $this->scope;

        if ($scope === []) {
            return ['totals' => ['employees' => 0, 'active' => 0, 'contracts' => 0, 'ending' => 0], 'departments' => [], 'levels' => [], 'types' => [], 'none' => []];
        }

        return [
            'totals' => $stats->totals($scope),
            'departments' => $stats->byDepartment($scope),
            'levels' => $stats->byJobLevel($scope),
            'types' => $stats->byContractType($scope),
            'none' => $stats->withoutLeaves($scope),
        ];
    }
}; ?>

@php
    $figures = $this->figures;
    $totals = $figures['totals'];
    $charts = [
        'by_department' => $figures['departments'],
        'by_job_level' => $figures['levels'],
        'by_contract_type' => $figures['types'],
    ];
@endphp

<x-desk.register heading-id="hr-statistics-heading" :title="__('hr_desk.statistics.title')">
    <x-slot:figures>{{ __('hr_desk.statistics.lede') }}</x-slot:figures>

    <div class="grid gap-8">
        <dl class="grid grid-cols-2 gap-4 @3xl:grid-cols-4">
            @foreach (['active' => 'active_employees', 'contracts' => 'running_contracts', 'ending' => 'ending_soon', 'employees' => 'employees'] as $key => $label)
                <div class="rounded-lg border border-secondary-soft p-4 dark:border-line">
                    <dt class="type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_desk.stats.'.$label) }}</dt>
                    <dd class="text-2xl font-bold tabular-nums">{{ number_format($totals[$key]) }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="grid gap-8 @5xl:grid-cols-3">
            @foreach ($charts as $key => $rows)
                <section aria-labelledby="hr-stat-{{ $key }}">
                    <h2 id="hr-stat-{{ $key }}" class="mb-2 font-semibold">{{ __('hr_desk.statistics.'.$key) }}</h2>
                    <x-ui.chart
                        type="bar"
                        horizontal
                        distributed
                        :label="__('hr_desk.statistics.'.$key)"
                        :series="[['name' => __('hr_desk.statistics.count'), 'data' => array_column(array_slice($rows, 0, 12), 'count')]]"
                        :categories="array_column(array_slice($rows, 0, 12), 'label')"
                        :height="max(200, min(12, count($rows)) * 30)"
                        :empty="__('hr_desk.statistics.empty')"
                    />
                </section>
            @endforeach
        </div>

        <section aria-labelledby="hr-stat-none">
            <h2 id="hr-stat-none" class="font-semibold">{{ __('hr_desk.statistics.no_leaves') }} <span class="dk-pc dk-num">{{ count($figures['none']) }}</span></h2>
            <p class="type-meta mb-2 text-secondary-medium dark:text-ink-muted">{{ __('hr_desk.statistics.no_leaves_hint') }}</p>
            @if ($figures['none'] === [])
                <p>{{ __('hr_desk.statistics.empty') }}</p>
            @else
                <ul class="grid gap-1 @3xl:grid-cols-3">
                    @foreach ($figures['none'] as $person)
                        <li wire:key="none-{{ $person['id'] }}">
                            @if (Route::has('hr.employees.show'))
                                <a href="{{ route('hr.employees.show', $person['id']) }}" wire:navigate class="hover:underline">{{ $person['name'] }}</a>
                            @else
                                {{ $person['name'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-desk.register>
