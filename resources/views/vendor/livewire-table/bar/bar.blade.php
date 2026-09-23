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
            <div class="perpage-select-box w-24" data-tour="table.per-page">
                <x-forms.select2-input wire:model.live="perPage" :hideSearch="true">
                    @foreach($perPageOptions as $perPage)
                        <option wire:key="{{ $perPage }}" value="{{ $perPage }}">{{ $perPage }}</option>
                    @endforeach
                </x-forms.select2-input>
            </div>
        </div>
    </div>
</div>
