<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.my-leaves')"
            :crumbs="[
                ['label' => __('general.holidays-and-leaves')],

            ]"
            :showButton="true"
        />
        <div>
            <x-primary-button :href="route('employees.create')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>

    <livewire:employee.leave.leave-request-table />
</div>
