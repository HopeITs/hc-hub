<div>
    <livewire:layout.breadcrumbs
        :title="trans_choice('general.contract-type',2)"
        :crumbs="[
        ['label' => __('general.dashboard')],
        ['label' => trans_choice('general.contract-type',2)],
    ]"
    />
    <x-datatable.table :title="trans_choice('general.city',2)" :items="$items"
                       :headings="['ID' , __('general.type') , __('general.place') ,__('general.weekly_hours')]">

        @forelse($items as $item)
            @php
                $place = \App\Enums\ContractPlace::getLocalizedName($item->place);
            @endphp
            <tr>
                <x-datatable.td>{{$item->id}}</x-datatable.td>

                <x-datatable.td>{{$item->type}}</x-datatable.td>

                <x-datatable.td>
                    <x-badge :color="$place['color']"> {{ $place['value'] }}</x-badge>
                </x-datatable.td>
                <x-datatable.td>{{$item->weekly_hours}}</x-datatable.td>
            </tr>
        @empty
            <x-datatable.no-data/>
        @endforelse
    </x-datatable.table>

</div>
