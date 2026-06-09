<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs :title="trans_choice('general.employee', 2)" :crumbs="[
            ['label' => trans_choice('general.employee-management', 2)],
            ['label' => trans_choice('general.employee', 2)],
        ]" :showButton="true" />
        @activerole('HR', 'super-admin')
        <div class="flex items-center gap-2">
            <x-excel-export-button wire:click="$dispatchTo('employee.employee-table', 'exportExcel')" />
            <x-primary-button :href="route('employees.create')">
                @lang('general.add-new')
            </x-primary-button>

        </div>
        @endactiverole
    </div>

    <livewire:employee.employee-table />
</div>
