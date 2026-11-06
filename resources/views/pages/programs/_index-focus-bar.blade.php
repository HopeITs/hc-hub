{{--
    The three plain answers at the top of programs/index: how many programs
    need attention (late projects, live risks, projects without funding), are not
    set up yet (no strategy, no manager or no projects), or are on track. Each is
    a button that narrows the list; pressing it again shows everything. The three
    counts are disjoint. Included by ⚡index.blade.php, which defines
    $focusCounts and $filtersAreActive.
--}}
@php
    $tabs = [
        ['key' => 'attention', 'label' => __('programs.tab_attention'), 'tone' => 'text-status-warn'],
        ['key' => 'setup', 'label' => __('programs.tab_setup'), 'tone' => 'text-secondary-dark dark:text-ink'],
        ['key' => 'ontrack', 'label' => __('programs.tab_ontrack'), 'tone' => 'text-status-good'],
    ];
@endphp

<div class="flex flex-wrap items-stretch gap-2" data-test="program-focus-tabs">
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

    @if ($filtersAreActive && $visiblePrograms->isNotEmpty())
        <flux:button size="sm" variant="ghost" class="self-center text-secondary-medium! dark:text-ink-muted!" wire:click="clearFilters" data-test="reset-filters">{{ __('common.reset_filters') }}</flux:button>
    @endif
</div>
