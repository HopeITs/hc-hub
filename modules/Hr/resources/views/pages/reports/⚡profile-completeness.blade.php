<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Core\Models\City;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Reports\ProfileCompletenessReport;
use Modules\Hr\Support\Reports\ReportCatalog;
use Modules\Hr\Support\Reports\ReportCsv;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Profile completeness: which employee files are missing what. Presence only,
 * no value is read into the report (ProfileCompletenessReport). HR and head
 * office with employees.view; a city HR sees its own city.
 */
new #[Layout('layouts::desk'), Title('hr_reports.reports.profile-completeness.title')] class extends Component {
    #[Url(as: 'city')]
    public ?string $cityId = null;

    #[Url(as: 'missing')]
    public ?string $missing = null;

    public function mount(): void
    {
        $this->authorizeReport();
    }

    public function export(): StreamedResponse
    {
        $this->authorizeReport();
        $r = $this->report()->build();
        $t = fn (string $key): string => __('hr_reports.csv.'.$key);

        return ReportCsv::download('profile-completeness-'.now()->format('Y-m-d').'.csv', [
            ['title' => $t('summary'), 'headings' => [$t('metric'), $t('value')], 'rows' => [
                [$t('people'), $r['people']],
                [__('hr_reports.completeness.complete'), $r['complete']],
                [__('hr_reports.completeness.average'), $r['averagePercent'] ?? '-'],
            ]],
            ['title' => __('hr_reports.completeness.by_item'), 'headings' => [$t('item'), $t('people_with_it'), $t('share')],
                'rows' => $r['byItem']->map(fn (array $i): array => [$i['label'], $i['count'], $i['percent'].'%'])->all()],
            ['title' => __('hr_reports.completeness.by_city'), 'headings' => [$t('city'), $t('people'), $t('average')],
                'rows' => $r['byCity']->map(fn (array $c): array => [$c['label'], $c['count'], $c['percent']])->all()],
            ['title' => __('hr_reports.completeness.files'), 'headings' => [$t('name'), $t('city'), $t('completeness'), $t('missing')],
                'rows' => $r['list']->map(fn (array $p): array => [$p['name'], $p['city'], $p['percent'], implode(', ', $p['missingLabels'])])->all()],
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
     * @return array<int, string>
     */
    #[Computed]
    public function cities(): array
    {
        $cityIds = Access::activeCityIds(Auth::user());

        return City::query()->when($cityIds !== null, fn ($q) => $q->whereIn('id', $cityIds))->orderBy('id')->get()->pluck('name', 'id')->all();
    }

    private function report(): ProfileCompletenessReport
    {
        return new ProfileCompletenessReport(
            Auth::user(),
            filled($this->cityId) ? (int) $this->cityId : null,
            filled($this->missing) ? $this->missing : null,
        );
    }

    private function authorizeReport(): void
    {
        abort_unless(ReportCatalog::allows('profile-completeness', Auth::user()), 403);
    }
}; ?>

@php
    $r = $this->r;
    $limit = ProfileCompletenessReport::LIST_LIMIT;
@endphp

<x-desk.register class="@container" heading-id="hr-report-completeness-heading" :title="__('hr_reports.reports.profile-completeness.title')" :count="$r['people']">
    <x-slot:figures>{{ __('hr_reports.reports.profile-completeness.lead') }} {{ __('hr_reports.completeness.in_force') }}</x-slot:figures>

    <x-slot:filters>
        <x-ui.header-filter-select id="completeness-city" :label="__('hr_reports.city')" wire:model.live="cityId" :value="(string) $cityId">
            <option value="">{{ __('hr_reports.all') }}</option>
            @foreach ($this->cities as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </x-ui.header-filter-select>

        <x-ui.header-filter-select id="completeness-missing" :label="__('hr_reports.completeness.missing')" wire:model.live="missing" :value="(string) $missing" data-testid="completeness-missing">
            <option value="">{{ __('hr_reports.completeness.anything') }}</option>
            @foreach (ProfileCompletenessReport::labels() as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    <x-slot:actions>
        <x-ui.button tone="action" icon="arrow-down-tray" wire:click="export" data-test="export-report">{{ __('hr_reports.export') }}</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-4" wire:loading.class="opacity-60">
        <div class="grid grid-cols-2 gap-4 @3xl:grid-cols-3">
            <x-ui.stat-tile :label="__('hr_reports.completeness.average')" :value="$r['averagePercent'] === null ? '-' : $r['averagePercent'].'%'" icon="chart-bar" data-testid="completeness-average" />
            <x-ui.stat-tile :label="__('hr_reports.completeness.complete')" :value="number_format($r['complete'])" :denominator="number_format($r['people'])" icon="document-text" tone="good" data-testid="completeness-complete" />
            <x-ui.stat-tile :label="__('hr_reports.completeness.incomplete')" :value="number_format($r['people'] - $r['complete'])" icon="users" tone="warn" data-testid="completeness-incomplete" />
        </div>

        <div class="grid gap-4 @3xl:grid-cols-2">
            <x-ui.panel :title="__('hr_reports.completeness.by_item')">
                <div class="p-4"><x-hr::report.bars :rows="$r['byItem']" /></div>
            </x-ui.panel>
            <x-ui.panel :title="__('hr_reports.completeness.by_city')">
                <div class="p-4"><x-hr::report.bars :rows="$r['byCity']" /></div>
            </x-ui.panel>
        </div>

        <x-ui.panel :title="__('hr_reports.completeness.files')" :count="$r['listTotal']">
            @if ($r['list']->isEmpty())
                <p class="p-4 type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.nothing') }}</p>
            @else
                <ul class="divide-y divide-panel-border" data-testid="completeness-list">
                    @foreach ($r['list']->take($limit) as $p)
                        <li class="flex flex-wrap items-baseline justify-between gap-2 px-4 py-2 text-sm" wire:key="completeness-{{ $loop->index }}">
                            <span class="text-secondary-dark dark:text-ink">
                                @if (filled($p['username']))
                                    <a href="{{ route('hr.employees.show', $p['username']) }}" class="underline" wire:navigate>{{ $p['name'] }}</a>
                                @else
                                    {{ $p['name'] }}
                                @endif
                                <span class="text-secondary-medium dark:text-ink-muted">&middot; {{ $p['city'] }}</span>
                            </span>
                            <span class="text-secondary-medium dark:text-ink-muted"><span class="tabular-nums">{{ $p['percent'] }}%</span> &middot; {{ implode('، ', $p['missingLabels']) }}</span>
                        </li>
                    @endforeach
                </ul>
                @if ($r['listTotal'] > $limit)
                    <p class="px-4 pb-3 type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.showing', ['a' => $limit, 'b' => $r['listTotal']]) }}</p>
                @endif
            @endif
        </x-ui.panel>
    </div>
</x-desk.register>
