<div>

    <livewire:layout.breadcrumbs
        :title="trans_choice('general.city', 2)"
        :crumbs="[
         ['label' => __('general.project-basics')],
        ['label' => trans_choice('general.city', 2), 'route' => 'cities.index'],
    ]"
    />

    <x-table>
        <x-slot name="headings">
            <x-table.heading
                sortable
            >ID
            </x-table.heading>

            <x-table.heading
                sortable
                wire:click="sortBy('name_ar')"
                :direction="$sortField === 'name_ar' ? $sortDirection : null"
            >@lang('general.name')(AR)
            </x-table.heading>

            <x-table.heading
                sortable
                wire:click="sortBy('name_en')"
                :direction="$sortField === 'name_en' ? $sortDirection : null"
            >@lang('general.name')(EN)
            </x-table.heading>

            <x-table.heading
                sortable
                wire:click="sortBy('code')"
                :direction="$sortField === 'code' ? $sortDirection : null"
            >@lang('general.code')</x-table.heading>

            <x-table.heading
                sortable
                wire:click="sortBy('slug')"
                :direction="$sortField === 'slug' ? $sortDirection : null"
            >@lang('general.slug')</x-table.heading>


            @activerole('HR')
            <x-table.heading
                sortable
                wire:click="sortBy('is_active')"
                :direction="$sortField === 'is_active' ? $sortDirection : null"
            >@lang('general.status')</x-table.heading>
            @endactiverole
        </x-slot>

        <x-slot name="body">
            @forelse($cities as $item)
                <tr>
                    <x-table.cell>{{ $item->id }}</x-table.cell>
                    <x-table.cell>{{ $item->name_ar ?? '-' }}</x-table.cell>
                    <x-table.cell>{{ $item->name_en ?? '-' }}</x-table.cell>
                    <x-table.cell>{{ $item->code ?? '-' }}</x-table.cell>
                    <x-table.cell>{{ $item->slug ?? '-' }}</x-table.cell>

                    @activerole('HR')
                        <x-table.cell-status
                            :active="$item->is_active"
                            :model-id="$item->id"
                            wire-event="confirmToggleItemStatus"
                        />
                    @endactiverole
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
