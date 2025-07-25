<div>
    <livewire:layouts.breadcrumbs
        :title="trans_choice('general.city',2)"
        :crumbs="[
        ['label' => __('general.dashboard')],
        ['label' => trans_choice('general.city',2)],
    ]"
    />
    <x-datatable.table :title="trans_choice('general.city',2)" :items="$cities"
                       :headings="['ID' , __('general.name') , __('general.status') ]">

        @foreach($cities as $city)
            <tr>
                <x-datatable.td>{{$city->id}}</x-datatable.td>
                <x-datatable.td>{{$city->name}}</x-datatable.td>
                <x-datatable.td-status :active="$city->is_active"/>
            </tr>
        @endforeach
    </x-datatable.table>

</div>
