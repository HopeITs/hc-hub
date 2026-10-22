<div class="flex flex-col gap-3 relative"
    wire:init="init"
    {{-- The Clear filters button disappears with the empty state; hand focus to the search box so keyboard users keep their place. --}}
    x-on:list-filters-cleared="$nextTick(() => $el.querySelector('input[type=search]')?.focus())"
    @if(strlen($polling = $this->polling()) > 0) wire:poll.{{ $polling }} @endif
>
    @include('livewire-table::bar.bar')
    @if(method_exists($this, 'stateAnnouncement'))
        {{-- Polite announcement of the result count / state after every search, filter or page change;
             wire:loading text covers the in-between so the change is never silent. --}}
        <p class="sr-only" role="status" aria-live="polite" aria-atomic="true">
            <span wire:loading.remove>{{ $this->stateAnnouncement($paginator->total()) }}</span>
            <span wire:loading>{{ __('general.loading') }}</span>
        </p>
    @endif
    <div
            @if(method_exists($this, 'stateAnnouncement')) wire:loading.class="opacity-60" wire:target="globalSearch,search,filters,perPage,clearAllFilters,retryLoad,gotoPage,previousPage,nextPage,setPage" @endif
            class="overflow-x-auto bg-white border border-[var(--line-hairline)] shadow-e1 rounded-surface overscroll-x-none">
        @include('livewire-table::table.table')
    </div>
    {{ $paginator->links('livewire-table::pagination.pagination') }}


</div>


