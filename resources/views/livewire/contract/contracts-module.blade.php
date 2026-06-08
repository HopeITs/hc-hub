@php use App\Enums\SalaryPer; @endphp
<div>
    @php($locale = app()->getLocale())

    <div class="flex justify-between mb-6">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.contracts', 2)"
            :crumbs="[
                ['label' => trans_choice('general.employee-management', 2)],
                ['label' => trans_choice('general.contracts', 2)],
            ]"
            :showButton="true"
        />
        @activerole('HR', 'super-admin')
        <div class="flex items-center gap-2">
            <x-primary-button wire:click="$dispatchTo('contract.contracts-table', 'exportExcel')" :showSpinner="true">
                @lang('general.export-excel')
            </x-primary-button>
            <x-primary-button :href="route('contracts.create')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
        @endactiverole
    </div>

    <livewire:contract.contracts-table />
</div>