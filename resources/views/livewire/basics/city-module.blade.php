<x-slot:title>
    {{ trans_choice('general.city', 2) }}
</x-slot:title>

<div>

    @php($locale = app()->getLocale())
    <div class="flex justify-between items-center mb-6">

        <livewire:layout.breadcrumbs :title="trans_choice('general.city', 2)" :crumbs="[
            ['label' => __('general.project-basics')],
            ['label' => trans_choice('general.city', 2), 'route' => 'cities.index'],
        ]" />

        @activerole('HR','super-admin')
            <div class="flex items-center gap-2">
                <x-excel-export-button wire:click="$dispatchTo('basics.city-table', 'exportExcel')" />
            </div>
        @endactiverole

    </div>
    <livewire:basics.city-table />
</div>
