<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.department', 2)"
            :crumbs="[
                ['label' => __('general.project-basics')],
                ['label' => trans_choice('general.department', 2), 'route' => route('departments.index')],
            ]"
            :showButton="false"
        />
    </div>

    <livewire:basics.department-table />
</div>
