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
        <div>
            <x-primary-button :href="route('contracts.create')">
                @lang('general.add-new')
            </x-primary-button>
        </div>
    </div>

    <livewire:contract.contracts-table />
</div>