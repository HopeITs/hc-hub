<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Core\Models\Program;
use Modules\Hr\Livewire\Concerns\Statistics\ReadsStatisticsScope;
use Modules\Hr\Support\Statistics\HrFigures;

/**
 * Contract statistics (the old Contract statistics page): one card per
 * program, or per center once a program is picked, with its employees and
 * running contracts split into annual, service and trial. Positions count
 * in a city through their own city or their center's. Figures are grouped
 * queries remembered ten minutes (HrFigures::contractCards).
 */
new #[Layout('layouts::desk'), Title('hr_statistics.titles.contracts')] class extends Component {
    use ReadsStatisticsScope;

    #[Url(except: '')]
    public string $city = '';

    #[Url(except: '')]
    public string $program = '';

    public function mount(): void
    {
        $this->authorizeStatistics();
        $this->normaliseFilters();
    }

    public function updated(): void
    {
        $this->normaliseFilters();
    }

    public function pickProgram(int $programId): void
    {
        $this->program = (string) $programId;
        $this->normaliseFilters();
    }

    public function clearFilters(): void
    {
        $this->reset(['city', 'program']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Computed]
    public function cards(): array
    {
        return app(HrFigures::class)->contractCards($this->citiesFor($this->city), $this->program === '' ? null : (int) $this->program);
    }

    protected function normaliseFilters(): void
    {
        if ($this->city !== '' && ! array_key_exists((int) $this->city, $this->cityOptions)) {
            $this->city = '';
        }

        if ($this->program !== '' && ! array_key_exists((int) $this->program, Program::namesById())) {
            $this->program = '';
        }
    }
}; ?>

@php
    $cards = $this->cards;
    $byProgram = $program === '';
    $hasFilters = $city !== '' || $program !== '';
    $groups = [
        'annual' => ['full', 'part'],
        'service' => ['online', 'onsite'],
        'trial' => ['full', 'part'],
    ];
@endphp

<x-desk.register class="@container" heading-id="hr-statistics-contracts-heading" :title="__('hr_statistics.titles.contracts')" :count="count($cards)">
    <x-slot:figures>
        {{ $byProgram ? __('hr_statistics.contracts.lede_programs') : __('hr_statistics.contracts.lede_centers', ['program' => Program::nameOf((int) $program)]) }}
        · {{ __('hr_statistics.contracts.employees') }} <b class="dk-num">{{ number_format(array_sum(array_column($cards, 'employees'))) }}</b>
        · {{ __('hr_statistics.contracts.contracts') }} <b class="dk-num">{{ number_format(array_sum(array_column($cards, 'contracts'))) }}</b>
    </x-slot:figures>

    <x-slot:filters>
        @if (count($this->cityOptions) > 1)
            <x-ui.header-filter-select id="hr-contract-stats-city" :label="__('hr_statistics.filters.city')" wire:model.live="city" :value="$city">
                <option value="">{{ __('hr_statistics.filters.all_cities') }}</option>
                @foreach ($this->cityOptions as $cityId => $cityName)
                    <option value="{{ $cityId }}">{{ $cityName }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif

        <x-ui.header-filter-select id="hr-contract-stats-program" :label="__('hr_statistics.filters.program')" wire:model.live="program" :value="$program">
            <option value="">{{ __('hr_statistics.filters.all_programs') }}</option>
            @foreach (Program::namesById() as $programId => $programName)
                <option value="{{ $programId }}">{{ $programName }}</option>
            @endforeach
        </x-ui.header-filter-select>

        @if ($hasFilters)
            <x-ui.button tone="ghost" icon="x-mark" size="sm" wire:click="clearFilters">{{ __('hr_statistics.filters.clear') }}</x-ui.button>
        @endif
    </x-slot:filters>

    <x-slot:actions>
        <x-ui.button icon="chart-pie" :href="route('hr.statistics.general')" wire:navigate>{{ __('hr_statistics.titles.general') }}</x-ui.button>
    </x-slot:actions>

    <div wire:loading.class="opacity-60" wire:target="city, program, pickProgram, clearFilters">
        @if ($cards === [])
            <x-ui.panel>
                <p class="p-6 text-center type-meta text-secondary-medium dark:text-ink-muted">{{ $hasFilters ? __('hr_statistics.contracts.empty_filtered') : __('hr_statistics.empty') }}</p>
            </x-ui.panel>
        @else
            <div class="grid gap-4 @3xl:grid-cols-2 @6xl:grid-cols-3">
                @foreach ($cards as $card)
                    <x-ui.panel as="article" wire:key="contract-card-{{ $card['id'] }}" class="flex flex-col">
                        <div class="flex items-start justify-between gap-3 p-4 pb-2">
                            <div class="min-w-0">
                                <h2 class="truncate text-base font-bold text-secondary-dark dark:text-ink">{{ $card['name'] }}</h2>
                                <p class="type-meta text-secondary-medium dark:text-ink-muted">
                                    {{ __('hr_statistics.contracts.employees') }} <b class="tabular-nums text-secondary-dark dark:text-ink">{{ number_format($card['employees']) }}</b>
                                    @if ($card['centers'] !== null)
                                        · {{ __('hr_statistics.contracts.centers') }} <b class="tabular-nums text-secondary-dark dark:text-ink">{{ number_format($card['centers']) }}</b>
                                    @endif
                                </p>
                            </div>
                            <flux:badge size="sm">{{ $byProgram ? __('hr_statistics.contracts.program') : __('hr_statistics.contracts.center') }}</flux:badge>
                        </div>

                        <div class="grid grid-cols-3 gap-2 p-4 pt-2">
                            @foreach ($groups as $group => $slots)
                                <div class="rounded-lg border border-secondary-soft p-2 dark:border-line">
                                    <p class="mb-1 border-b border-secondary-soft pb-1 text-xs font-semibold dark:border-line">{{ __('hr_statistics.contracts.groups.'.$group) }}</p>
                                    <dl class="grid gap-0.5 text-xs">
                                        @foreach ($slots as $slot)
                                            <div class="flex justify-between gap-1"><dt class="text-secondary-medium dark:text-ink-muted">{{ __('hr_statistics.contracts.slots.'.$slot) }}</dt><dd class="tabular-nums font-semibold">{{ $card[$group][$slot] }}</dd></div>
                                        @endforeach
                                        <div class="mt-0.5 flex justify-between gap-1 border-t border-secondary-soft pt-0.5 dark:border-line"><dt class="text-secondary-medium dark:text-ink-muted">{{ __('hr_statistics.total') }}</dt><dd class="tabular-nums font-bold">{{ array_sum($card[$group]) }}</dd></div>
                                    </dl>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-auto flex items-center justify-end gap-2 border-t border-panel-border px-4 py-2">
                            @if ($byProgram)
                                <x-ui.button tone="ghost" size="sm" icon="building-office-2" wire:click="pickProgram({{ $card['id'] }})">{{ __('hr_statistics.contracts.show_centers') }}</x-ui.button>
                            @endif
                            @can('contracts.view')
                                <x-ui.button tone="ghost" size="sm" icon-trailing="arrow-right" class="rtl:[&_svg]:rotate-180" :href="route('hr.contracts.index', array_filter(['program' => $byProgram ? $card['id'] : (int) $program, 'city' => $city]))" wire:navigate>{{ __('hr_statistics.contracts.open_contracts') }}</x-ui.button>
                            @endcan
                        </div>
                    </x-ui.panel>
                @endforeach
            </div>
        @endif
    </div>
</x-desk.register>
