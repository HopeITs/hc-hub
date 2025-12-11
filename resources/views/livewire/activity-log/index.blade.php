<div>
    <livewire:layout.breadcrumbs
        :title="__('general.activity-log')"
        :crumbs="[
            ['label' => __('general.activity-log')],
        ]"
    />

    <livewire:activity-log.activity-log-table />
</div>
