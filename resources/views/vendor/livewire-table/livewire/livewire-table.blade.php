<div class="flex flex-col gap-3 relative"
    wire:init="init"
    @if(strlen($polling = $this->polling()) > 0) wire:poll.{{ $polling }} @endif
>
    @include('livewire-table::bar.bar')
    <div
            class="overflow-x-auto bg-white border border-secondary-dark  shadow-xs rounded-md overscroll-x-none">
        @include('livewire-table::table.table')
    </div>
    {{ $paginator->links('livewire-table::pagination.pagination') }}

    @isset($showModal)
        @if($showModal)
          {!! $modalBody !!}
        @endif

    @endisset
</div>


