<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.positions')"
            :crumbs="[
        ['label' => __('general.programs-management')],
        ['label' => __('general.positions') ],
            ]"
            :showButton="true"
        />
        <div class="flex items-center gap-2">
            <x-primary-button :href="route('positions.create')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>
    <livewire:position.position-table/>
</div>
