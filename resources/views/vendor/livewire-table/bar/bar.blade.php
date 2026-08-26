<div class="flex flex-col gap-3">
    <div class="flex flex-col lg:flex-row gap-3">
        @includeWhen($this->canSearch(), 'livewire-table::bar.search')
        <div class="justify-center items-center w-full border-y border-transparent" wire:loading.flex>
            <span class="inline-block border border-4 border-primary border-r-transparent motion-safe:animate-spin rounded-full my-2 p-2"></span>
        </div>
        @include('livewire-table::bar.selection')
        <div class="flex gap-3 ml-auto">
            @includeWhen($this->useReordering, 'livewire-table::bar.buttons.reordering')
            @include('livewire-table::bar.buttons.refresh')
            @include('livewire-table::bar.dropdowns.polling')
            @include('livewire-table::bar.dropdowns.columns')
            @include('livewire-table::bar.dropdowns.filters')
            @include('livewire-table::bar.dropdowns.actions')
            @include('livewire-table::bar.dropdowns.trashed')
            <select wire:model.live="perPage"
                    class="border border-neutral-200 shadow-e1 rounded-control outline-hidden focus:border-secondary-medium ps-3 pe-8 py-2 bg-white text-black transition ease-in-out dark:bg-neutral-800 dark:border-neutral-700 dark:focus:border-primary dark:text-white">
                @foreach($perPageOptions as $perPage)
                    <option wire:key="{{ $perPage }}" value="{{ $perPage }}">{{ $perPage }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
