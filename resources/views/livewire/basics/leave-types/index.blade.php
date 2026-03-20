<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.leave-type', 2)"
            :crumbs="[
                 ['label' => __('general.project-basics')],
                ['label' => trans_choice('general.leave-type', 2)],
            ]"
            :showButton="true"
        />
        @activerole('HR','super-admin')
        <div>
            <x-primary-button wire:click="$emit('createLeaveType')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
        @endactiverole
    </div>

    <livewire:basics.leave-type-table />
</div>
