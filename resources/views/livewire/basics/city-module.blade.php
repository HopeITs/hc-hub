<div>
    <livewire:layout.breadcrumbs
        :title="trans_choice('general.city',2)"
        :crumbs="[
        ['label' => __('general.project-basics')],
        ['label' => trans_choice('general.city',2)],
    ]"
    />
    <x-table>
        <x-slot name="headings">
            <x-table.heading>ID</x-table.heading>
            <x-table.heading sortable> @lang('general.name')(AR)</x-table.heading>
            <x-table.heading sortable> @lang('general.name')(EN)</x-table.heading>
            <x-table.heading>@lang('general.code')</x-table.heading>
            <x-table.heading>@lang('general.status')</x-table.heading>
        </x-slot>
        <x-slot name="body">
            @forelse($cities as $item)
                <tr>
                    <x-table.cell>{{$item->code}}</x-table.cell>
                    <x-table.cell>{{$item->getTranslation('name' , 'ar')}}</x-table.cell>
                    <x-table.cell>{{$item->getTranslation('name' , 'en')}}</x-table.cell>
                    <x-table.cell>{{$item->slug}}</x-table.cell>
                    <x-table.cell-status :active="$item->is_active"
                                         :model-id="$item->id"
                                         wire-event="confirmToggleItemStatus"/>
                </tr>
            @empty
                <x-table.no-data/>
            @endforelse
        </x-slot>
        <x-slot name="pagination">
            {{ $cities->links() }}
        </x-slot>
    </x-table>


</div>
