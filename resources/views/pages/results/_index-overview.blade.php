{{-- The page's one question: which statements need a hand? Three numbers, each a filter; pressing the active one clears it. --}}
@php
    $focusCounts = $this->focusCounts;

    $headline = [
        ['key' => 'unmeasured', 'hint' => __('results_framework.hint_unmeasured'), 'label' => __('results_framework.needs_an_indicator'), 'icon' => 'exclamation-triangle', 'count' => $focusCounts['unmeasured']],
        ['key' => 'offtrack', 'hint' => __('results_framework.hint_offtrack'), 'label' => __('results_framework.behind'), 'icon' => 'arrow-trending-down', 'count' => $focusCounts['offtrack']],
        ['key' => 'waiting', 'hint' => __('results_framework.hint_waiting'), 'label' => __('results_framework.waiting_for_a_measurement'), 'icon' => 'clock', 'count' => $focusCounts['waiting']],
    ];
@endphp

<div class="space-y-2" data-test="framework-overview">
    <div class="grid grid-cols-3 gap-2 @xl/directory:gap-3 [&>*]:min-w-0">
        @foreach ($headline as $cell)
            <flux:tooltip :content="$cell['hint']">
            <button
                type="button"
                wire:click="setFocus('{{ $cell['key'] }}')"
                wire:key="framework-headline-{{ $cell['key'] }}"
                aria-pressed="{{ $focus === $cell['key'] ? 'true' : 'false' }}"
                data-test="focus-chip-{{ $cell['key'] }}"
                @class([
                    'flex h-full min-h-11 w-full items-start justify-between gap-2 rounded-xl border p-3 text-start @xl/directory:items-center @xl/directory:gap-3 @xl/directory:p-4 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary-medium',
                    'border-primary bg-white shadow-card dark:bg-surface-1' => $focus === $cell['key'],
                    'border-secondary-soft bg-white hover:border-secondary-light dark:border-line dark:bg-surface-1' => $focus !== $cell['key'],
                ])
            >
                <span class="min-w-0 flex-1">
                    <span class="block type-label text-[10px]! tracking-normal! break-words @md/directory:text-xs! text-secondary-medium dark:text-ink-muted">{{ $cell['label'] }}</span>
                    <span @class([
                        'mt-1 block type-figure',
                        'text-status-warn' => $cell['count'] > 0,
                        'text-secondary-dark dark:text-ink' => $cell['count'] === 0,
                    ])>{{ number_format($cell['count']) }}</span>
                    <span class="mt-0.5 block type-meta text-secondary-medium dark:text-ink-muted">{{ __('results_framework.count_of_total_statements', ['count' => $cell['count'], 'total' => $overview['statements']]) }}</span>
                </span>
                <span class="sr-only">{{ $cell['hint'] }}</span>
                <flux:icon :name="$cell['icon']" class="hidden size-5 shrink-0 text-secondary-medium @md/directory:block dark:text-ink-muted" aria-hidden="true" />
            </button>
            </flux:tooltip>
        @endforeach
    </div>

    @if ($overview['statements'] > 0)
        <p class="type-meta text-secondary-medium dark:text-ink-muted">
            {{ __('results_framework.measuring_count_of_total_statements', ['count' => $overview['measured'], 'total' => $overview['statements']]) }}
            · {{ trans_choice('programs.logframe_count_indicators', $overview['indicators'], ['count' => $overview['indicators']]) }}
            · {{ trans_choice('common.count_target', $overview['targets'], ['count' => $overview['targets']]) }}
        </p>
    @endif
</div>
