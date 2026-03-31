<div>

    @php($locale = app()->getLocale())
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs :title="trans_choice('general.city', 2)" :crumbs="[
            ['label' => __('general.project-basics')],
            ['label' => trans_choice('general.city', 2), 'route' => 'cities.index'],
        ]" />
    </div>

    <livewire:basics.city-table />
</div>
