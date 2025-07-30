<div>
    <livewire:layout.breadcrumbs
        :title="trans_choice('general.contract-type',2)"
        :crumbs="[
        ['label' => __('general.dashboard')],
        ['label' => trans_choice('general.contract-type',2)],
    ]"
    />
    <x-table>
        <x-slot name="headings">
            <x-table.heading>ID</x-table.heading>
            <x-table.heading sortable> @lang('general.type')</x-table.heading>
            <x-table.heading>@lang('general.place')</x-table.heading>
            <x-table.heading>@lang('general.weekly_hours')</x-table.heading>
        </x-slot>
        <x-slot name="body">
            @forelse($items as $item)
                @php
                    $place = \App\Enums\ContractPlace::getLocalizedName($item->place);
                @endphp
                <tr>
                    <x-table.cell>{{$item->id}}</x-table.cell>

                    <x-table.cell>{{$item->type}}</x-table.cell>

                    <x-table.cell>
                        <x-badge :color="$place['color']"> {{ $place['value'] }}</x-badge>
                    </x-table.cell>
                    <x-table.cell>{{$item->weekly_hours}}</x-table.cell>
                </tr>
            @empty
                <x-table.no-data/>
            @endforelse
        </x-slot>
        <x-slot name="pagination">
            {{$items->links()}}
        </x-slot>
    </x-table>


</div>
