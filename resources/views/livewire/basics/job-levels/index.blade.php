<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.job-level', 2)"
            :crumbs="[
                ['label' => __('general.project-basics')],
                ['label' => trans_choice('general.job-level', 2), 'route' => route('job-levels.index')],
            ]"
            :showButton="false"
        />
    </div>

    <livewire:basics.job-level-table />
</div>
