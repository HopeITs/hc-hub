<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Hr\Livewire\Concerns\Statistics\ReadsStatisticsScope;
use Modules\Hr\Support\Statistics\HrFigures;

/**
 * Salary statistics (the old "employees per salary type" card): how many
 * people are paid by the month, session or hour, read on each person's
 * newest contract, overall and per program. Pay is HR's to read
 * (HrAccess::canViewSalary): anyone else, the super admin included, gets
 * a notice instead of the figures.
 */
new #[Layout('layouts::desk'), Title('hr_statistics.titles.salaries')] class extends Component {
    use ReadsStatisticsScope;

    #[Url(except: '')]
    public string $city = '';

    public function mount(): void
    {
        $this->authorizeStatistics();
        $this->normaliseFilters();
    }

    public function updated(): void
    {
        $this->normaliseFilters();
    }

    #[Computed]
    public function mayReadPay(): bool
    {
        return $this->canViewSalary();
    }

    /**
     * @return array{periods: array<int, int>, programs: list<array{program: string, counts: array<int, int>}>}
     */
    #[Computed]
    public function figures(): array
    {
        if (! $this->mayReadPay) {
            return ['periods' => [], 'programs' => []];
        }

        $cities = $this->citiesFor($this->city);
        $figures = app(HrFigures::class);

        return [
            'periods' => $figures->salaryPeriods($cities),
            'programs' => $figures->salaryPeriodsByProgram($cities),
        ];
    }

    protected function normaliseFilters(): void
    {
        if ($this->city !== '' && ! array_key_exists((int) $this->city, $this->cityOptions)) {
            $this->city = '';
        }
    }
}; ?>

@use('Modules\Hr\Enums\SalaryPer')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $figures = $this->figures;
    $periods = HrFigures::SalaryPeriods;
    $labels = array_map(fn (SalaryPer $per): string => EnumLabel::of(SalaryPer::class, $per->value), $periods);
    $counts = array_map(fn (SalaryPer $per): int => $figures['periods'][$per->value] ?? 0, $periods);
    $total = array_sum($counts);
    $programs = $figures['programs'];
@endphp

<x-desk.register class="@container" heading-id="hr-statistics-salaries-heading" :title="__('hr_statistics.titles.salaries')" :count="$this->mayReadPay ? $total : null">
    <x-slot:figures>{{ __('hr_statistics.salaries.lede') }}</x-slot:figures>

    @if ($this->mayReadPay && count($this->cityOptions) > 1)
        <x-slot:filters>
            <x-ui.header-filter-select id="hr-salary-stats-city" :label="__('hr_statistics.filters.city')" wire:model.live="city" :value="$city">
                <option value="">{{ __('hr_statistics.filters.all_cities') }}</option>
                @foreach ($this->cityOptions as $cityId => $cityName)
                    <option value="{{ $cityId }}">{{ $cityName }}</option>
                @endforeach
            </x-ui.header-filter-select>
        </x-slot:filters>
    @endif

    <x-slot:actions>
        <x-ui.button icon="chart-pie" :href="route('hr.statistics.general')" wire:navigate>{{ __('hr_statistics.titles.general') }}</x-ui.button>
    </x-slot:actions>

    @if (! $this->mayReadPay)
        <x-ui.panel>
            <div class="flex items-start gap-3 p-6">
                <flux:icon.lock-closed class="size-5 shrink-0 text-secondary-medium dark:text-ink-muted" />
                <div>
                    <p class="font-semibold">{{ __('hr_statistics.salaries.hidden_title') }}</p>
                    <p class="type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_statistics.salaries.hidden_body') }}</p>
                </div>
            </div>
        </x-ui.panel>
    @else
        <div class="grid gap-6" wire:loading.class="opacity-60" wire:target="city">
            <div class="grid grid-cols-2 gap-4 @3xl:grid-cols-4">
                <x-ui.stat-tile :label="__('hr_statistics.salaries.people')" :value="number_format($total)" icon="users" />
                @foreach ($periods as $index => $per)
                    <x-ui.stat-tile :label="$labels[$index]" :value="number_format($counts[$index])" :unit="$total > 0 ? '· '.round($counts[$index] / $total * 100).'%' : null" icon="banknotes" />
                @endforeach
            </div>

            <div class="grid gap-6 @5xl:grid-cols-3">
                <x-ui.panel :title="__('hr_statistics.salaries.by_period')" :subtitle="__('hr_statistics.salaries.hint')">
                    <div class="p-4" wire:key="salary-donut-{{ $city }}">
                        <x-ui.chart
                            type="donut"
                            :label="__('hr_statistics.salaries.by_period')"
                            :series="$counts"
                            :categories="$labels"
                            :tones="['strong', 'neutral', 'muted']"
                            :total-label="__('hr_statistics.salaries.people')"
                            :height="240"
                            :empty="__('hr_statistics.empty')"
                        />
                    </div>
                </x-ui.panel>

                <x-ui.panel :title="__('hr_statistics.salaries.by_program')" class="@5xl:col-span-2">
                    <div class="p-4" wire:key="salary-programs-{{ $city }}">
                        <x-ui.chart
                            type="bar"
                            horizontal
                            :label="__('hr_statistics.salaries.by_program')"
                            :series="array_map(fn (int $index): array => ['name' => $labels[$index], 'data' => array_map(fn (array $row): int => $row['counts'][$periods[$index]->value] ?? 0, $programs)], array_keys($periods))"
                            :categories="array_column($programs, 'program')"
                            :tones="['strong', 'neutral', 'muted']"
                            :height="max(220, count($programs) * 44)"
                            :empty="__('hr_statistics.empty')"
                        />
                    </div>
                </x-ui.panel>
            </div>

            @if ($programs !== [])
                <x-ui.panel :title="__('hr_statistics.salaries.table')">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-panel-border text-start type-meta text-secondary-medium dark:text-ink-muted">
                                    <th scope="col" class="px-4 py-2 text-start font-semibold">{{ __('hr_statistics.filters.program') }}</th>
                                    @foreach ($labels as $label)
                                        <th scope="col" class="px-4 py-2 text-end font-semibold">{{ $label }}</th>
                                    @endforeach
                                    <th scope="col" class="px-4 py-2 text-end font-semibold">{{ __('hr_statistics.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($programs as $row)
                                    <tr wire:key="salary-row-{{ $loop->index }}" class="border-b border-panel-border last:border-0">
                                        <th scope="row" class="px-4 py-2 text-start font-medium">{{ $row['program'] }}</th>
                                        @foreach ($periods as $per)
                                            <td class="px-4 py-2 text-end tabular-nums">{{ $row['counts'][$per->value] ?? 0 }}</td>
                                        @endforeach
                                        <td class="px-4 py-2 text-end font-semibold tabular-nums">{{ array_sum($row['counts']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.panel>
            @endif
        </div>
    @endif
</x-desk.register>
