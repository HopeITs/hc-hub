<div class="flex flex-col gap-3 relative"
    wire:init="init"
    @if(strlen($polling = $this->polling()) > 0) wire:poll.{{ $polling }} @endif
>
    @include('livewire-table::bar.bar')
    <div
        class="overflow-x-auto bg-white border border-primary-200 dark:bg-primary-900 dark:border-primary-700 shadow-xs rounded-md overscroll-x-none">
        @include('livewire-table::table.table')
    </div>
    {{ $paginator->links('livewire-table::pagination.pagination') }}
</div>
