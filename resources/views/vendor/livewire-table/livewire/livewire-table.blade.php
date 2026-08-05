<div class="flex flex-col gap-3 relative"
    wire:init="init"
    @if(strlen($polling = $this->polling()) > 0) wire:poll.{{ $polling }} @endif
>
    @include('livewire-table::bar.bar')
    <div
            class="overflow-x-auto bg-white border border-[var(--line-hairline)] shadow-e1 rounded-surface overscroll-x-none">
        @include('livewire-table::table.table')
    </div>
    {{ $paginator->links('livewire-table::pagination.pagination') }}


</div>


