<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.employee', 2)"
            :crumbs="[
                ['label' => trans_choice('general.employee-management', 2)],
                ['label' => trans_choice('general.employee', 2)],
            ]"
            :showButton="true"
        />
        <div>
            <x-primary-button :href="route('employees.create')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>

    <livewire:employee.employee-table />
</div>
