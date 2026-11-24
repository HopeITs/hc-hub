<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Hr\Support\Reports\RecruitmentFunnelReport;
use Modules\Hr\Support\Reports\ReportCatalog;
use Modules\Hr\Support\Reports\ReportCsv;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Recruitment funnel: from request to hire, aggregates only, inside the
 * viewer's recruitment scope (RecruitmentFunnelReport). No applicant is named.
 */
new #[Layout('layouts::desk'), Title('hr_reports.reports.recruitment-funnel.title')] class extends Component {
    #[Url(as: 'period')]
    public int $days = 365;

    public function mount(): void
    {
        $this->authorizeReport();
    }

    public function export(): StreamedResponse
    {
        $this->authorizeReport();
        $r = $this->report()->build();
        $t = fn (string $key): string => __('hr_reports.csv.'.$key);
        $f = fn (string $key): string => __('hr_reports.funnel.'.$key);

        return ReportCsv::download('recruitment-funnel-'.now()->format('Y-m-d').'.csv', [
            ['title' => $f('where'), 'headings' => [$t('stage'), $t('applications'), $t('share'), $t('from_previous')],
                'rows' => $r['stages']->map(fn (array $s): array => [$s['label'], $s['count'], $s['percent'].'%', $s['fromPrevious'] ?? '-'])->all()],
            ['title' => $t('summary'), 'headings' => [$t('metric'), $t('value')], 'rows' => [
                [$f('requests_submitted'), $r['requests']['submitted']],
                [$f('requests_approved'), $r['requests']['approved']],
                [$f('requests_rejected'), $r['requests']['rejected']],
                [$f('requests_cancelled'), $r['requests']['cancelled']],
                [$f('requests_in_review'), $r['requests']['inReview']],
                [$f('days_to_vacancy'), $r['requests']['daysToApply'] ?? '-'],
                [$f('days_to_interview'), $r['times']['toInterview'] ?? '-'],
                [$f('days_to_offer'), $r['times']['toOffer'] ?? '-'],
                [$f('days_to_hire'), $r['times']['toHire'] ?? '-'],
                [$f('acceptance'), $r['offerAcceptancePercent'] ?? '-'],
                [$f('offers_expired'), $r['offersExpired']],
            ]],
            ['title' => $f('by_status'), 'headings' => [$t('status'), $t('applications'), $t('share')],
                'rows' => $r['byStatus']->map(fn (array $x): array => [$x['label'], $x['count'], $x['percent'].'%'])->all()],
            ['title' => $f('positions'), 'headings' => [$t('position'), $t('applications'), $t('interviewed'), $t('offers'), $t('hired')],
                'rows' => $r['byPosition']->map(fn (array $p): array => [$p['label'], $p['applications'], $p['interviewed'], $p['offers'], $p['hired']])->all()],
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

    private function report(): RecruitmentFunnelReport
    {
        return new RecruitmentFunnelReport(Auth::user(), $this->days);
    }

    private function authorizeReport(): void
    {
        abort_unless(ReportCatalog::allows('recruitment-funnel', Auth::user()), 403);
    }
}; ?>

@php
    $r = $this->r;
    $dash = fn ($value) => $value ?? '-';
@endphp

<x-desk.register class="@container" heading-id="hr-report-funnel-heading" :title="__('hr_reports.reports.recruitment-funnel.title')">
    <x-slot:figures>{{ __('hr_reports.reports.recruitment-funnel.lead') }}</x-slot:figures>

    <x-slot:filters>
        <x-ui.header-filter-select id="funnel-period" :label="__('hr_reports.period')" wire:model.live="days" :value="(string) $r['days']">
            @foreach (RecruitmentFunnelReport::PERIODS as $period)
                <option value="{{ $period }}">{{ $period === 0 ? __('hr_reports.all_time') : __('hr_reports.last_days', ['n' => $period]) }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    <x-slot:actions>
        <x-ui.button tone="action" icon="arrow-down-tray" wire:click="export" data-test="export-report">{{ __('hr_reports.export') }}</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-4" wire:loading.class="opacity-60">
        <div class="grid grid-cols-2 gap-4 @3xl:grid-cols-4">
            <x-ui.stat-tile :label="__('hr_reports.funnel.requests_submitted')" :value="number_format($r['requests']['submitted'])" icon="document-text" data-testid="funnel-requests" />
            <x-ui.stat-tile :label="__('hr_reports.funnel.days_to_vacancy')" :value="$dash($r['requests']['daysToApply'])" icon="clock" />
            <x-ui.stat-tile :label="__('hr_reports.funnel.days_to_hire')" :value="$dash($r['times']['toHire'])" icon="clock" data-testid="funnel-to-hire" />
            <x-ui.stat-tile :label="__('hr_reports.funnel.acceptance')" :value="$r['offerAcceptancePercent'] === null ? '-' : $r['offerAcceptancePercent'].'%'" icon="check-circle" tone="good" data-testid="funnel-acceptance" />
        </div>

        <p class="type-meta text-secondary-medium dark:text-ink-muted">
            {{ __('hr_reports.funnel.requests_line', ['a' => $r['requests']['approved'], 'b' => $r['requests']['rejected'], 'c' => $r['requests']['inReview']]) }}
            &middot; {{ __('hr_reports.funnel.offers_expired_n', ['n' => $r['offersExpired']]) }}
        </p>

        <x-ui.panel :title="__('hr_reports.funnel.where')">
            <div class="p-4">
                @foreach ($r['stages'] as $stage)
                    <div class="mb-3 last:mb-0" data-testid="funnel-stage" wire:key="funnel-stage-{{ $stage['key'] }}">
                        <div class="flex items-baseline justify-between gap-2 text-sm">
                            <span class="text-secondary-dark dark:text-ink">{{ $stage['label'] }}</span>
                            <span class="tabular-nums text-secondary-medium dark:text-ink-muted">{{ $stage['count'] }} &middot; {{ $stage['percent'] }}%@if ($stage['fromPrevious'] !== null) &middot; {{ __('hr_reports.funnel.of_previous', ['n' => $stage['fromPrevious']]) }}@endif</span>
                        </div>
                        <div class="mt-1 h-3 rounded-full bg-secondary-soft dark:bg-surface-2"><div class="h-3 rounded-full bg-secondary-dark dark:bg-ink-muted" style="width: {{ $stage['percent'] }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </x-ui.panel>

        <div class="grid gap-4 @3xl:grid-cols-3">
            <x-ui.stat-tile :label="__('hr_reports.funnel.days_to_interview')" :value="$dash($r['times']['toInterview'])" icon="clock" />
            <x-ui.stat-tile :label="__('hr_reports.funnel.days_to_offer')" :value="$dash($r['times']['toOffer'])" icon="clock" />
            <x-ui.panel :title="__('hr_reports.funnel.by_status')">
                <div class="p-4"><x-hr::report.bars :rows="$r['byStatus']" /></div>
            </x-ui.panel>
        </div>

        <x-ui.panel :title="__('hr_reports.funnel.positions')">
            @if ($r['byPosition']->isEmpty())
                <p class="p-4 type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_reports.nothing') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="type-label text-secondary-medium dark:text-ink-muted">
                            <tr>
                                <th class="px-4 py-2 text-start">{{ __('hr_reports.vacancies.position') }}</th>
                                <th class="px-4 py-2 text-end">{{ __('hr_reports.funnel.applications') }}</th>
                                <th class="px-4 py-2 text-end">{{ __('hr_reports.funnel.stages.interviewed') }}</th>
                                <th class="px-4 py-2 text-end">{{ __('hr_reports.funnel.stages.offer_sent') }}</th>
                                <th class="px-4 py-2 text-end">{{ __('hr_reports.funnel.stages.hired') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-panel-border">
                            @foreach ($r['byPosition'] as $row)
                                <tr wire:key="funnel-position-{{ $loop->index }}">
                                    <td class="px-4 py-2 text-secondary-dark dark:text-ink">{{ $row['label'] }}</td>
                                    <td class="px-4 py-2 text-end tabular-nums">{{ $row['applications'] }}</td>
                                    <td class="px-4 py-2 text-end tabular-nums">{{ $row['interviewed'] }}</td>
                                    <td class="px-4 py-2 text-end tabular-nums">{{ $row['offers'] }}</td>
                                    <td class="px-4 py-2 text-end tabular-nums">{{ $row['hired'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.panel>
    </div>
</x-desk.register>
