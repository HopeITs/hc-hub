@php
    use App\Enums\LeaveTypeCases;
use App\Enums\LeaveRequestStatus;
@endphp
<div>
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="__('general.my-leaves')"
            :crumbs="[
                ['label' => __('general.holidays-and-leaves')],

            ]"
            :showButton="true"
        />


    </div>


    {{-- Table --}}
    <div class="mt-4">
        <livewire:employee.leave.emp-leave-balance-table  key="table-balance"/>
    </div>


    <x-modal.dialog max-width="6xl" id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>

        <form wire:submit.prevent="save" class="space-y-4">
            <x-forms.errors-validation-list/>
            <div class="grid md:grid-cols-2 gap-4">

            </div>
            <div class="flex justify-end gap-2">
                <x-primary-button type="button" wire:click="confirmSave"
                                  class="!w-auto">@lang('general.save')</x-primary-button>
                <x-primary-button :href="route('leaves.index')"
                                  class="!bg-secondary-light !w-auto">@lang('general.cancel')</x-primary-button>
            </div>
        </form>
    </x-modal.dialog>
</div>
