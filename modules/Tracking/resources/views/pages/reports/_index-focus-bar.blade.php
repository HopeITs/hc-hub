{{--
    The three plain answers at the top of reports/index: how many reports need a
    hand (late drafts, and reports waiting for approval), are drafts still in
    progress, or are approved. Each is a button that narrows the feed; pressing
    it again shows everything. The counts are disjoint and add up to the total.
    Included by ⚡index.blade.php, which defines $focusCounts and
    $filtersAreActive.
--}}
@php
    $tabs = [
        ['key' => 'attention', 'label' => __('reports.tab_attention'), 'tone' => 'text-status-warn'],
        ['key' => 'progress', 'label' => __('reports.tab_progress'), 'tone' => 'text-secondary-dark dark:text-ink'],
        ['key' => 'approved', 'label' => __('reports.tab_approved'), 'tone' => 'text-status-good'],
    ];
@endphp

<div class="flex flex-wrap items-stretch gap-2" data-test="report-focus-tabs">
    @foreach ($tabs as $tab)
        <button
            type="button"
            wire:click="setFocus('{{ $tab['key'] }}')"
            wire:key="focus-tab-{{ $tab['key'] }}"
            aria-pressed="{{ $focus === $tab['key'] ? 'true' : 'false' }}"
            data-test="focus-tab-{{ $tab['key'] }}"
            @class([
                'flex min-h-14 flex-1 basis-40 cursor-pointer items-center gap-3 rounded-xl border px-4 py-2 text-start transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary-medium',
                'border-secondary-dark bg-white shadow-card ring-1 ring-secondary-dark dark:border-ink dark:bg-surface-2 dark:ring-ink' => $focus === $tab['key'],
                'border-secondary-soft bg-white hover:border-secondary-light dark:border-line dark:bg-surface-1' => $focus !== $tab['key'],
            ])
        >
            <span class="text-2xl font-bold leading-none tabular-nums {{ $tab['tone'] }}">{{ $focusCounts[$tab['key']] }}</span>
            <span class="text-sm font-semibold text-secondary-dark dark:text-ink">{{ $tab['label'] }}</span>
        </button>
    @endforeach

    @if ($filtersAreActive && in_array($view, ['board', 'calendar'], true))
        <flux:button size="sm" variant="ghost" class="self-center text-secondary-medium! dark:text-ink-muted!" wire:click="clearFilters" data-test="reset-filters">{{ __('common.reset_filters') }}</flux:button>
    @endif
</div>
