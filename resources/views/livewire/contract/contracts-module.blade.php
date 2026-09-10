@php use App\Enums\SalaryPer; @endphp
<x-slot:title>
    {{ trans_choice('general.contracts', 2) }}
</x-slot:title>

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
             <x-excel-export-button wire:click="$dispatchTo('contract.contracts-table', 'exportExcel')" />
            <x-primary-button :href="route('contracts.create')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
        @endactiverole
    </div>

    {{-- Keeps the employee and position columns pinned while the rest of this
         wide table scrolls sideways. Scoped here on purpose: every other table
         in the app shares the same vendor view and is left alone. --}}
    <div class="table-frozen-2">
        <livewire:contract.contracts-table />
    </div>

    {{-- One instance for the whole table; each row's Renew button addresses it
         by dispatching `open-renew-modal`. --}}
    <livewire:contract.contract-renew-modal />
</div>