<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Models\Department;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Reports\PositionVacanciesReport;
use Modules\Hr\Support\Reports\ReportCatalog;
use Modules\Hr\Support\Reports\ReportCsv;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Position vacancies: capacity against the people in post, by city and
 * department, with the critical positions (PositionVacanciesReport). HR and
 * head office with positions.view; a city HR sees its own city.
 */
new #[Layout('layouts::desk'), Title('hr_reports.reports.vacancies.title')] class extends Component {
    /** @var array<string, int|string|bool|null> */
    #[Url(as: 'f')]
    public array $filters = [];

    public function mount(): void
    {
        $this->authorizeReport();
    }

    public function clearFilters(): void
    {
        $this->filters = [];
    }

    public function export(): StreamedResponse
    {
        $this->authorizeReport();
        $r = $this->report()->build();
        $t = fn (string $key): string => __('hr_reports.csv.'.$key);
        $groups = fn ($rows): array => $rows->map(fn (array $g): array => [$g['label'], $g['positions'], $g['capacity'], $g['filled'], $g['vacant'], $g['percent'] === null ? '-' : $g['percent'].'%'])->all();
        $heads = fn (string $first): array => [$first, $t('positions'), $t('capacity'), $t('in_post'), $t('vacant'), $t('filled')];

        return ReportCsv::download('position-vacancies-'.now()->format('Y-m-d').'.csv', [
            ['title' => $t('summary'), 'headings' => [$t('metric'), $t('value')], 'rows' => [
                [$t('positions'), $r['positions']],
                [$t('capacity'), $r['capacity']],
                [$t('in_post'), $r['filled']],
                [__('hr_reports.vacancies.vacant_seats'), $r['vacantSeats']],
                [$t('filled'), $r['fillPercent'] ?? '-'],
                [__('hr_reports.vacancies.nobody'), $r['emptyPositions']],
                [__('hr_reports.vacancies.over_capacity'), $r['overPositions']],
                [__('hr_reports.vacancies.critical_vacant'), $r['criticalVacant']],
            ]],
            ['title' => __('hr_reports.vacancies.by_city'), 'headings' => $heads($t('city')), 'rows' => $groups($r['byCity'])],
            ['title' => __('hr_reports.vacancies.by_department'), 'headings' => $heads($t('department')), 'rows' => $groups($r['byDepartment'])],
            ['title' => $t('positions'), 'headings' => [$t('position'), $t('city'), $t('department'), $t('program'), $t('capacity'), $t('in_post'), $t('vacant'), $t('over'), $t('critical')],
                'rows' => $r['list']->map(fn (array $p): array => [$p['title'], $p['city'], $p['department'], $p['program'], $p['capacity'], $p['filled'], $p['vacant'], $p['over'], $p['critical'] ? __('hr_reports.yes') : ''])->all()],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function r(): array
    {
        $this->authorizeReport();

        return $this->report()->build();
    }

    /**
     * id => label, per filter.
     *
     * @return array<string, array<int, string>>
     */
    #[Computed]
    public function options(): array
    {
        $cityIds = Access::activeCityIds(Auth::user());

        return [
            'city_id' => City::query()->when($cityIds !== null, fn ($q) => $q->whereIn('id', $cityIds))->orderBy('id')->get()->pluck('name', 'id')->all(),
            'department_id' => Department::query()->orderBy('name')->pluck('name', 'id')->all(),
            'program_id' => Program::namesById(),
        ];
    }

    private function report(): PositionVacanciesReport
    {
        return new PositionVacanciesReport(Auth::user(), $this->filters);
    }

    private function authorizeReport(): void
    {
        abort_unless(ReportCatalog::allows('vacancies', Auth::user()), 403);
    }
}; ?>

@php
    $r = $this->r;
    $limit = PositionVacanciesReport::LIST_LIMIT;
    $states = ['all', 'vacant', 'empty', 'full', 'over'];
    $hasFilters = collect($filters)->filter(fn ($value) => filled($value) && $value !== false && $value !== 'all')->isNotEmpty();
@endphp

<x-desk.register class="@container" heading-id="hr-report-vacancies-heading" :title="__('hr_reports.reports.vacancies.title')" :count="$r['positions']">
    <x-slot:figures>{{ __('hr_reports.reports.vacancies.lead') }}</x-slot:figures>

    <x-slot:filters>
        @foreach (['city_id' => 'city', 'department_id' => 'department', 'program_id' => 'program'] as $key => $label)
            <x-ui.header-filter-select id="vacancy-filter-{{ $key }}" :label="__('hr_reports.'.$label)" wire:model.live="filters.{{ $key }}" :value="(string) ($filters[$key] ?? '')">
                <option value="">{{ __('hr_reports.all') }}</option>
                @foreach ($this->options[$key] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endforeach

        <x-ui.header-filter-select id="vacancy-filter-state" :label="__('hr_reports.vacancies.show')" wire:model.live="filters.state" :value="(string) ($filters['state'] ?? 'all')">
            @foreach ($states as $state)
                <option value="{{ $state }}">{{ __('hr_reports.vacancies.states.'.$state) }}</option>
            @endforeach
        </x-ui.header-filter-select>

        <x-ui.header-filter-select id="vacancy-filter-critical" :label="__('hr_reports.vacancies.critical')" wire:model.live="filters.critical" :value="(string) ($filters['critical'] ?? '')">
            <option value="">{{ __('hr_reports.all') }}</option>
            <option value="1">{{ __('hr_reports.vacancies.critical_only') }}</option>
        </x-ui.header-filter-select>

        @if ($hasFilters)
            <x-ui.button tone="ghost" icon="x-mark" size="sm" wire:click="clearFilters">{{ __('hr_reports.clear_filters') }}</x-ui.button>
        @endif
    </x-slot:filters>

    <x-slot:actions>
        <x-ui.button tone="action" icon="arrow-down-tray" wire:click="export" data-test="export-report">{{ __('hr_reports.export') }}</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-4" wire:loading.class="opacity-60">
        <div class="grid grid-cols-2 gap-4 @3xl:grid-cols-4">
            <x-ui.stat-tile :label="__('hr_reports.vacancies.positions')" :value="number_format($r['positions'])" icon="briefcase" data-testid="vacancy-positions" />
            <x-ui.stat-tile :label="__('hr_reports.vacancies.vacant_seats')" :value="number_format($r['vacantSeats'])" :denominator="number_format($r['capacity'])" icon="users" data-testid="vacancy-seats" />
            <x-ui.stat-tile :label="__('hr_reports.vacancies.filled')" :value="$r['fillPercent'] === null ? '-' : $r['fillPercent'].'%'" icon="chart-bar" tone="good" data-testid="vacancy-fill" />
            <x-ui.stat-tile :label="__('hr_reports.vacancies.critical_vacant')" :value="$r['criticalVacant']" icon="clock" tone="warn" data-testid="vacancy-critical" />
        </div>

        <p class="type-meta text-secondary-medium dark:text-ink-muted">
            {{ __('hr_reports.vacancies.in_post_of', ['a' => $r['filled'], 'b' => $r['capacity']]) }}
            &middot; {{ __('hr_reports.vacancies.nobody_and_over', ['a' => $r['emptyPositions'], 'b' => $r['overPositions']]) }}
        </p>

        <div class="grid gap-4 @3xl:grid-cols-2">
            @foreach ([['by_city', $r['byCity']], ['by_department', $r['byDepartment']]] as [$heading, $groups])
                <x-ui.panel :title="__('hr_reports.vacancies.'.$heading)" wire:key="vacancy-group-{{ $heading }}">
                    <div class="p-4">
                        @forelse ($groups as $g)
                            <div class="mb-3 last:mb-0">
                                <div class="flex items-baseline justify-between gap-2 text-sm">
                                    <span class="text-secondary-dark dark:text-ink">{{ $g['label'] }}</span>
                                    <span class="tabular-nums text-secondary-medium dark:text-ink-muted">{{ $g['filled'] }} / {{ $g['capacity'] }} &middot; {{ __('hr_reports.vacancies.vacant_n', ['n' => $g['vacant']]) }}</span>
                                </div>
                                <div class="mt-1 h-2 rounded-full bg-secondary-soft dark:bg-surface-2"><div class="h-2 rounded-full bg-secondary-dark dark:bg-ink-muted" style="width: {{ min(100, $g['percent'] ?? 0) }}%"></div></div>
                            </div>
                        @empty
                            <p class="type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.nothing') }}</p>
                        @endforelse
                    </div>
                </x-ui.panel>
            @endforeach
        </div>

        <x-ui.panel :title="__('hr_reports.vacancies.positions')" :count="$r['listTotal']">
            @if ($r['list']->isEmpty())
                <p class="p-4 type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.nothing') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" data-testid="vacancy-list">
                        <thead class="type-label text-secondary-medium dark:text-ink-muted">
                            <tr>
                                <th class="px-4 py-2 text-start">{{ __('hr_reports.vacancies.position') }}</th>
                                <th class="px-4 py-2 text-start">{{ __('hr_reports.city') }}</th>
                                <th class="px-4 py-2 text-start">{{ __('hr_reports.department') }}</th>
                                <th class="px-4 py-2 text-end">{{ __('hr_reports.vacancies.in_post') }}</th>
                                <th class="px-4 py-2 text-end">{{ __('hr_reports.vacancies.capacity') }}</th>
                                <th class="px-4 py-2 text-end">{{ __('hr_reports.vacancies.vacant') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-panel-border">
                            @foreach ($r['list']->take($limit) as $p)
                                <tr wire:key="vacancy-{{ $p['id'] }}">
                                    <td class="px-4 py-2 text-secondary-dark dark:text-ink">{{ $p['title'] }} @if ($p['critical'])<x-ui.tone-badge tone="critical">{{ __('hr_reports.vacancies.critical') }}</x-ui.tone-badge>@endif</td>
                                    <td class="px-4 py-2 text-secondary-medium dark:text-ink-muted">{{ $p['city'] }}</td>
                                    <td class="px-4 py-2 text-secondary-medium dark:text-ink-muted">{{ $p['department'] }}</td>
                                    <td class="px-4 py-2 text-end tabular-nums">{{ $p['filled'] }}</td>
                                    <td class="px-4 py-2 text-end tabular-nums">{{ $p['capacity'] }}</td>
                                    <td class="px-4 py-2 text-end tabular-nums">{{ $p['over'] > 0 ? '+'.$p['over'].' '.__('hr_reports.vacancies.over') : $p['vacant'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($r['listTotal'] > $limit)
                    <p class="px-4 pb-3 type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.showing', ['a' => $limit, 'b' => $r['listTotal']]) }}</p>
                @endif
            @endif
        </x-ui.panel>
    </div>
</x-desk.register>
