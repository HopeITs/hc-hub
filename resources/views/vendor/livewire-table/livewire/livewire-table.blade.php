@php($cardView = method_exists($this, 'cardView') ? $this->cardView() : null)
<div class="flex flex-col gap-3 relative"
    wire:init="init"
    {{-- The Clear filters button disappears with the empty state; hand focus to the search box so keyboard users keep their place. --}}
    x-on:list-filters-cleared="$nextTick(() => $el.querySelector('input[type=search]')?.focus())"
    @if(strlen($polling = $this->polling()) > 0) wire:poll.{{ $polling }} @endif
>
    @include('livewire-table::bar.bar')

    @if(method_exists($this, 'savedViews'))
        {{-- HasSavedViews: personal views (table state only), owner + active role only. --}}
        @php($savedViews = $this->savedViews())
        <div x-data="{ open: false }" x-on:keydown.escape="open = false" x-on:click.outside="open = false" class="relative self-start">
            <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open.toString()" aria-controls="saved-views-panel-{{ $this->getId() }}"
                class="inline-flex min-h-11 items-center rounded-control border border-neutral-300 bg-white px-4 text-sm font-semibold text-neutral-800 hover:bg-neutral-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                {{ __('general.views-menu') }} ({{ $savedViews->count() }})
            </button>
            <div id="saved-views-panel-{{ $this->getId() }}" x-show="open" x-cloak class="absolute start-0 top-full z-30 mt-2 w-[min(24rem,calc(100vw-2rem))] rounded-surface border border-[var(--line-hairline)] bg-white p-3 shadow-e1">
                <p class="mb-2 text-small text-neutral-600">{{ __('general.views-help') }}</p>
                @error('savedViewName')
                    <p role="alert" class="mb-2 text-sm font-medium text-red-800">{{ $message }}</p>
                @enderror
                <ul class="flex flex-col gap-2" aria-label="{{ __('general.views-saved-list') }}">
                    @forelse($savedViews as $saved)
                        <li wire:key="saved-view-{{ $saved->id }}" x-data="{ renaming: false, name: @js($saved->name) }" class="flex flex-wrap items-center gap-2">
                            <button type="button" x-show="! renaming" wire:click="applyView({{ $saved->id }})" aria-label="{{ __('general.views-apply', ['name' => $saved->name]) }}"
                                class="inline-flex min-h-11 min-w-0 flex-1 items-center rounded-lg px-3 text-start text-sm font-medium hover:bg-neutral-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"><span class="truncate" dir="auto">{{ $saved->name }}</span></button>
                            <button type="button" x-show="! renaming" x-on:click="renaming = true; $nextTick(() => $refs.rename.focus())" aria-label="{{ __('general.views-rename', ['name' => $saved->name]) }}"
                                class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">{{ __('general.edit') }}</button>
                            <button type="button" x-show="! renaming" wire:click="deleteView({{ $saved->id }})" wire:confirm="{{ __('general.views-delete-confirm') }}" aria-label="{{ __('general.views-delete', ['name' => $saved->name]) }}"
                                class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">{{ __('general.delete') }}</button>
                            <form x-show="renaming" x-cloak class="flex w-full flex-wrap items-center gap-2"
                                x-on:submit.prevent="$wire.renameView({{ $saved->id }}, name).then(ok => { renaming = ! ok })"
                                x-on:keydown.escape.stop="renaming = false">
                                <input x-ref="rename" x-model="name" type="text" maxlength="60" required dir="auto" aria-label="{{ __('general.views-name') }}"
                                    class="min-h-11 min-w-0 flex-1 rounded-control border border-neutral-300 px-3 text-sm">
                                <button type="submit" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">{{ __('general.save') }}</button>
                                <button type="button" x-on:click="renaming = false" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">{{ __('general.cancel') }}</button>
                            </form>
                        </li>
                    @empty
                        <li class="text-sm text-neutral-600">{{ __('general.views-none') }}</li>
                    @endforelse
                </ul>
                <form class="mt-3 flex flex-wrap items-center gap-2" wire:submit="saveView(savedViewName)">
                    <label for="saved-view-name-{{ $this->getId() }}" class="sr-only">{{ __('general.views-name') }}</label>
                    <input id="saved-view-name-{{ $this->getId() }}" type="text" wire:model="savedViewName" maxlength="60" required dir="auto" placeholder="{{ __('general.views-name') }}"
                        class="min-h-11 min-w-0 flex-1 rounded-control border border-neutral-300 px-3 text-sm">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-control border border-primary bg-primary px-4 text-sm font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">{{ __('general.views-save-current') }}</button>
                </form>
            </div>
        </div>
    @endif

    @if(method_exists($this, 'activeFilterChips'))
        {{-- RemembersTableState: removable chips, Clear all and the reset notice. --}}
        @php($chips = $this->activeFilterChips())
        @if($this->stateReset)
            <div role="status" class="flex items-center justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                <span>{{ __('general.state-notice-filter-cleared') }}</span>
                <button type="button" wire:click="dismissStateNotice" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg px-3 font-semibold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">{{ __('general.state-dismiss') }}</button>
            </div>
        @endif
        @if($chips !== [] || $this->canClearAll())
            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="{{ __('general.state-active-filters') }}">
                @foreach($chips as $chip)
                    <span wire:key="chip-{{ $chip['group'] }}-{{ $chip['key'] }}" class="inline-flex min-h-11 max-w-full items-center gap-1 rounded-full border border-[var(--line-hairline)] bg-white ps-3 text-sm">
                        <span class="min-w-0 truncate"><span class="text-neutral-600">{{ $chip['label'] }}:</span> <bdi dir="auto" class="font-medium">{{ $chip['value'] }}</bdi></span>
                        <button type="button" wire:click="removeChip('{{ $chip['group'] }}', '{{ $chip['key'] }}')"
                            aria-label="{{ __('general.state-remove-filter', ['filter' => $chip['label'].': '.$chip['value']]) }}"
                            class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </span>
                @endforeach
                @if($this->canClearAll())
                    <button type="button" wire:click="clearAll" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg px-3 text-sm font-semibold text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">{{ __('general.state-clear-all') }}</button>
                @endif
            </div>
        @endif
    @endif

    @if(method_exists($this, 'stateAnnouncement'))
        {{-- Polite announcement of the result count / state after every search, filter or page change;
             wire:loading text covers the in-between so the change is never silent. --}}
        <p class="sr-only" role="status" aria-live="polite" aria-atomic="true">
            <span wire:loading.remove>{{ $this->stateAnnouncement($paginator->total()) }}</span>
            <span wire:loading>{{ __('general.loading') }}</span>
        </p>
    @endif

    @if($cardView)
        {{-- Phones: a list of cards instead of a table (nothing scrolls sideways). --}}
        {{-- The per-column search lives in the table header, which phones do not show: offer the select filters here. --}}
        @php($phoneFilters = $this->resolveColumns()->filter(fn ($c) => $c instanceof \RamonRietdijk\LivewireTables\Columns\SelectColumn && $c->isSearchable() && in_array($c->code(), $this->columns)))
        @if($phoneFilters->isNotEmpty())
            <div class="flex flex-col gap-2 md:hidden">
                @foreach($phoneFilters as $column)
                    <div wire:key="phone-filter-{{ $column->code() }}" class="rounded-surface border border-[var(--line-hairline)] bg-white">
                        <span class="block px-3 pt-2 text-small font-semibold text-secondary-medium">{{ $column->label() }}</span>
                        {{ $column->renderSearch() }}
                    </div>
                @endforeach
            </div>
        @endif
        <div class="md:hidden"
            @if(method_exists($this, 'stateAnnouncement')) wire:loading.class="opacity-60" wire:target="globalSearch,search,filters,perPage,clearAll,removeChip,clearAllFilters,retryLoad,gotoPage,previousPage,nextPage,setPage" @endif>
            @if($paginator->count() > 0)
                <ul class="flex flex-col gap-3">
                    @foreach($paginator->items() as $item)
                        <li wire:key="card-{{ $item->getKey() }}" class="min-w-0 break-words rounded-surface border border-[var(--line-hairline)] bg-white p-4 shadow-e1">
                            @include($cardView, ['item' => $item, 'link' => $this->link($item), 'table' => $this])
                        </li>
                    @endforeach
                </ul>
            @elseif(! ($this->deferLoading && ! $this->initialized))
                <div class="rounded-surface border border-[var(--line-hairline)] bg-white p-4">
                    @if(method_exists($this, 'renderEmptyState')) {!! $this->renderEmptyState() !!} @else <p class="py-12 text-center">@lang('No results')</p> @endif
                </div>
            @endif
        </div>
    @endif
    <div
            @if(method_exists($this, 'stateAnnouncement')) wire:loading.class="opacity-60" wire:target="globalSearch,search,filters,perPage,clearAll,removeChip,clearAllFilters,retryLoad,gotoPage,previousPage,nextPage,setPage" @endif
            @class([
                'overflow-x-auto bg-white border border-[var(--line-hairline)] shadow-e1 rounded-surface overscroll-x-none',
                'hidden md:block' => $cardView,
            ])>
        @include('livewire-table::table.table')
    </div>
    {{ $paginator->links('livewire-table::pagination.pagination') }}


</div>
