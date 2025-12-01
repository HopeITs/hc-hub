<div class="px-3 py-1">
    <input type="search"
           class="min-w-full border border-neutral-200 rounded-md shadow-xs outline-hidden bg-white text-black focus:border-primary-300 mr-auto px-2 py-1 font-normal transition ease-in-out dark:bg-neutral-900 dark:border-neutral-700 dark:focus:border-primary-600 dark:text-white h-8"
           placeholder="@lang('general.search')"
           wire:model.live.debounce.500ms="search.{{ $column->code() }}">
</div>
