<div>
    <div class="grid grid-cols-4 space-x-3 space-y-3">
        @foreach($items as $item)
            <x-card class="col-span-1" :title="$item->name">
                {{$item->description}}
            </x-card>
        @endforeach
    </div>


</div>
