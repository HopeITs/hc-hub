@props(['active' => 0, 'name' => 'finance-filters'])

@php
    /*
     * The Funding pages' filters: one Filters button in the page header, cut
     * from the same cloth as Export and the view toggle, and a centred dialog
     * behind it. The dialog edits a draft (resources/js/finance-filters.js):
     * nothing reaches the list until "Show results", and closing it any other
     * way discards the draft. Sections hang off a thread whose dots fill for
     * every filter that is set. `active` counts the filters applied now.
     */
    $count = is_bool($active) ? (int) $active : max(0, (int) $active);
@endphp

<div class="contents" x-data="financeFilters(@js($name))">
    <x-ui.button
        icon="adjustments-horizontal"
        tone="action"
        @class(['page-action-on' => $count > 0])
        x-on:click="open()"
        aria-haspopup="dialog"
        :aria-label="__('finance_tables.filters').' · '.trans_choice('finance_tables.filters_active', $count, ['count' => $count])"
        data-page-header-filters
        data-test="open-filters"
    >
        {{ __('finance_tables.filters') }}
        @if ($count > 0)
            <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-xs font-bold leading-5 tabular-nums text-white dark:bg-brand" data-filter-count>{{ $count }}</span>
        @endif
    </x-ui.button>

    <x-ui.modal :name="$name" size="md" :title="__('finance_tables.filters')" :description="__('finance_tables.filters_draft_hint')" cancel-test="cancel-filters" class="filter-dialog">
        <x-slot:mark>
            <span class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-secondary-soft/60 text-secondary-dark dark:bg-surface-2 dark:text-ink" aria-hidden="true">
                <flux:icon.adjustments-horizontal class="size-5" />
            </span>
        </x-slot:mark>

        <div {{ $attributes->class('filter-thread') }} data-finance-filters>
            {{ $slot }}
        </div>

        <x-slot:footer-meta>
            <button type="button" x-on:click="clearAll()" class="inline-flex items-center gap-1.5 text-sm font-semibold text-secondary-dark underline decoration-secondary-light underline-offset-4 transition-colors hover:decoration-secondary-dark dark:text-ink dark:decoration-line-strong dark:hover:decoration-ink" data-test="reset-filters">
                <flux:icon.arrow-uturn-left variant="micro" class="size-4" />
                {{ __('finance_tables.filters_clear_all') }}
            </button>
        </x-slot:footer-meta>

        <x-slot:actions>
            <x-ui.button tone="primary" type="button" class="page-action page-action-primary" x-on:click="apply()" data-test="apply-filters">
                {{ __('finance_tables.filters_apply') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.modal>
</div>
