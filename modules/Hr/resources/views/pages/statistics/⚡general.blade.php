<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Hr\Livewire\Concerns\Statistics\ReadsStatisticsScope;
use Modules\Hr\Support\Desk\HrStatistics;

/**
 * General HR statistics (the old Statistics page): head counts, employees
 * by department and job level, running contracts by type, personnel traffic
 * over time, the salary-period split (pay readers only) and who never asked
 * for annual leave. Figures are grouped queries remembered ten minutes
 * (HrStatistics).
 */
new #[Layout('layouts::desk'), Title('hr_statistics.titles.general')] class extends Component {
    use ReadsStatisticsScope;

    #[Url(except: '')]
    public string $city = '';

    #[Url(except: 'monthly')]
    public string $traffic = 'monthly';

    public function mount(): void
    {
        $this->authorizeStatistics();
        $this->normaliseFilters();
    }

    public function updated(): void
    {
        $this->normaliseFilters();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function figures(): array
    {
        $cities = $this->citiesFor($this->city);
        $stats = app(HrStatistics::class);

        return [
            'totals' => $stats->totals($cities),
            'departments' => $stats->byDepartment($cities),
            'levels' => $stats->byJobLevel($cities),
            'types' => $stats->byContractType($cities),
            'none' => $stats->withoutLeaves($cities),
            'traffic' => $stats->personnelTraffic($cities, $this->traffic),
            'salary' => $this->canViewSalary() ? $stats->salaryPeriods($cities) : null,
        ];
    }

    protected function normaliseFilters(): void
    {
        if ($this->city !== '' && ! array_key_exists((int) $this->city, $this->cityOptions)) {
            $this->city = '';
        }

        if (! in_array($this->traffic, HrStatistics::TrafficModes, true)) {
            $this->traffic = 'monthly';
        }
    }
}; ?>

@use('Modules\Hr\Enums\SalaryPer')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $figures = $this->figures;
    $totals = $figures['totals'];
    $flow = $figures['traffic'];
    $bars = [
        'by_department' => $figures['departments'],
        'by_job_level' => $figures['levels'],
    ];
@endphp

<x-desk.register class="@container" heading-id="hr-statistics-general-heading" :title="__('hr_statistics.titles.general')">
    <x-slot:figures>{{ __('hr_statistics.general.lede') }}</x-slot:figures>

    <x-slot:filters>
        @if (count($this->cityOptions) > 1)
            <x-ui.header-filter-select id="hr-stats-city" :label="__('hr_statistics.filters.city')" wire:model.live="city" :value="$city">
                <option value="">{{ __('hr_statistics.filters.all_cities') }}</option>
                @foreach ($this->cityOptions as $cityId => $cityName)
                    <option value="{{ $cityId }}">{{ $cityName }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif

        <x-ui.header-filter-select id="hr-stats-traffic" :label="__('hr_statistics.traffic.period')" wire:model.live="traffic" :value="$traffic">
            @foreach (HrStatistics::TrafficModes as $mode)
                <option value="{{ $mode }}">{{ __('hr_statistics.traffic.modes.'.$mode) }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    <x-slot:actions>
        <x-ui.button icon="chart-bar" :href="route('hr.statistics.contracts')" wire:navigate>{{ __('hr_statistics.titles.contracts') }}</x-ui.button>
        @if ($figures['salary'] !== null)
            <x-ui.button icon="banknotes" :href="route('hr.statistics.salaries')" wire:navigate>{{ __('hr_statistics.titles.salaries') }}</x-ui.button>
        @endif
    </x-slot:actions>

    <div class="grid gap-6" wire:loading.class="opacity-60" wire:target="city, traffic">
        <div class="grid grid-cols-2 gap-4 @3xl:grid-cols-4">
            <x-ui.stat-tile :label="__('hr_statistics.totals.active')" :value="number_format($totals['active'])" icon="user-group" tone="good" />
            <x-ui.stat-tile :label="__('hr_statistics.totals.contracts')" :value="number_format($totals['contracts'])" icon="document-text" />
            <x-ui.stat-tile :label="__('hr_statistics.totals.ending')" :value="number_format($totals['ending'])" icon="clock" tone="warn" />
            <x-ui.stat-tile :label="__('hr_statistics.totals.employees')" :value="number_format($totals['employees'])" icon="users" />
        </div>

        <x-ui.panel :title="__('hr_statistics.traffic.title')" :subtitle="__('hr_statistics.traffic.modes.'.$traffic)">
            <div class="p-4" wire:key="traffic-{{ $traffic }}-{{ $city }}">
                <x-ui.chart
                    type="bar"
                    :label="__('hr_statistics.traffic.title')"
                    :series="[
                        ['name' => __('hr_statistics.traffic.new'), 'data' => $flow['new']],
                        ['name' => __('hr_statistics.traffic.ended'), 'data' => $flow['ended']],
                        ['name' => __('hr_statistics.traffic.terminated'), 'data' => $flow['terminated']],
                    ]"
                    :categories="$flow['labels']"
                    :tones="['good', 'warn', 'critical']"
                    :height="260"
                    :empty="__('hr_statistics.empty')"
                />
                <ul class="mt-3 flex flex-wrap gap-4 type-meta text-secondary-medium dark:text-ink-muted">
                    @foreach (['new' => 'bg-status-good', 'ended' => 'bg-status-warn', 'terminated' => 'bg-primary'] as $series => $swatch)
                        <li class="flex items-center gap-2"><span class="size-2 rounded-full {{ $swatch }}" aria-hidden="true"></span>{{ __('hr_statistics.traffic.'.$series) }} <b class="tabular-nums text-secondary-dark dark:text-ink">{{ array_sum($flow[$series]) }}</b></li>
                    @endforeach
                </ul>
            </div>
        </x-ui.panel>

        <div class="grid gap-6 @5xl:grid-cols-3">
            @foreach ($bars as $key => $rows)
                <x-ui.panel :title="__('hr_statistics.general.'.$key)" :count="array_sum(array_column($rows, 'count'))">
                    <div class="p-4" wire:key="bar-{{ $key }}-{{ $city }}">
                        <x-ui.chart
                            type="bar"
                            horizontal
                            distributed
                            :label="__('hr_statistics.general.'.$key)"
                            :series="[['name' => __('hr_statistics.count'), 'data' => array_column(array_slice($rows, 0, 12), 'count')]]"
                            :categories="array_column(array_slice($rows, 0, 12), 'label')"
                            :height="max(200, min(12, count($rows)) * 30)"
                            :empty="__('hr_statistics.empty')"
                        />
                    </div>
                </x-ui.panel>
            @endforeach

            <x-ui.panel :title="__('hr_statistics.general.by_contract_type')">
                <div class="p-4" wire:key="types-{{ $city }}">
                    <x-ui.chart
                        type="donut"
                        :label="__('hr_statistics.general.by_contract_type')"
                        :series="array_column($figures['types'], 'count')"
                        :categories="array_column($figures['types'], 'label')"
                        :total-label="__('hr_statistics.total')"
                        :height="240"
                        :empty="__('hr_statistics.empty')"
                    />
                    <dl class="mt-3 grid gap-1 type-meta">
                        @foreach ($figures['types'] as $row)
                            <div class="flex justify-between gap-2"><dt class="text-secondary-medium dark:text-ink-muted">{{ $row['label'] }}</dt><dd class="tabular-nums font-semibold">{{ number_format($row['count']) }}</dd></div>
                        @endforeach
                    </dl>
                </div>
            </x-ui.panel>
        </div>

        <div class="grid gap-6 @5xl:grid-cols-3">
            @if ($figures['salary'] !== null)
                <x-ui.panel :title="__('hr_statistics.salaries.by_period')" :subtitle="__('hr_statistics.salaries.hint')">
                    <div class="p-4" wire:key="salary-{{ $city }}">
                        <x-ui.chart
                            type="donut"
                            :label="__('hr_statistics.salaries.by_period')"
                            :series="array_map(fn (SalaryPer $per): int => $figures['salary'][$per->value] ?? 0, HrStatistics::SalaryPeriods)"
                            :categories="array_map(fn (SalaryPer $per): string => EnumLabel::of(SalaryPer::class, $per->value), HrStatistics::SalaryPeriods)"
                            :total-label="__('hr_statistics.salaries.people')"
                            :height="220"
                            :empty="__('hr_statistics.empty')"
                        />
                    </div>
                </x-ui.panel>
            @endif

            <x-ui.panel :title="__('hr_statistics.general.no_leaves')" :subtitle="__('hr_statistics.general.no_leaves_hint')" :count="count($figures['none'])" class="{{ $figures['salary'] !== null ? '@5xl:col-span-2' : '@5xl:col-span-3' }}">
                <div class="p-4">
                    @if ($figures['none'] === [])
                        <p class="type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_statistics.empty') }}</p>
                    @else
                        <ul class="grid gap-x-4 gap-y-1 @3xl:grid-cols-3">
                            @foreach ($figures['none'] as $person)
                                <li wire:key="none-{{ $person['id'] }}" class="truncate">{{ $person['name'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </x-ui.panel>
        </div>
    </div>
</x-desk.register>
