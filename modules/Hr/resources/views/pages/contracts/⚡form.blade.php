<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Contracts\SaveContract;
use Modules\Hr\Livewire\Concerns\Contracts\ManagesContractForm;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Employees\HrAccess;

new #[Layout('layouts::desk'), Title('hr_contracts.titles.form')] class extends Component {
    use ManagesContractForm;

    /**
     * Three routes share this page: create (?employee=), edit and renew.
     */
    public function mount(?Contract $contract = null): void
    {
        $user = Auth::user();
        $this->payHidden = ! HrAccess::canViewSalary($user);
        $isRenewal = request()->routeIs('hr.contracts.renew');

        if ($contract?->exists && $isRenewal) {
            Gate::authorize('renew', $contract);
            $this->renewalSourceId = $contract->id;
            $this->fillFromRenewal($contract);
        } elseif ($contract?->exists) {
            Gate::authorize('update', $contract);
            $this->contractId = $contract->id;
            $this->fillFromContract($contract);
        } else {
            Gate::authorize('create', Contract::class);
            $employeeId = request()->integer('employee') ?: null;
            $this->fillDefaults($employeeId);
        }
    }

    public function updatedFormEmployeeId(): void
    {
        unset($this->positionOptions);
    }

    public function updatedProgramFilter(): void
    {
        unset($this->positionOptions);
    }

    public function save(): void
    {
        $contract = $this->contractId === null ? null : Contract::query()->findOrFail($this->contractId);
        $source = $this->renewalSourceId === null ? null : Contract::query()->findOrFail($this->renewalSourceId);

        $saved = app(SaveContract::class)->handle($contract, $this->formData(), $this->tiers, Auth::user(), $source);

        Flux::toast(variant: 'success', text: __('hr_contracts.toasts.saved'));

        $this->redirectRoute('hr.contracts.show', ['contract' => $saved->id], navigate: true);
    }
};
?>

@use('App\Support\BackUrl')
@use('Modules\Core\Models\Program')
@use('Modules\Hr\Enums\ContractPlace')
@use('Modules\Hr\Enums\ContractType')
@use('Modules\Hr\Support\Contracts\ContractOptions')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $title = match (true) {
        $renewalSourceId !== null => __('hr_contracts.form.renew_title'),
        $contractId !== null => __('hr_contracts.form.edit_title'),
        default => __('hr_contracts.form.create_title'),
    };
    $grid = 'grid gap-x-4 gap-y-3 @2xl/form:grid-cols-2 @5xl/form:grid-cols-3';
    $category = $this->category();
    $canAutoRenew = $contractId !== null
        ? auth()->user()->can('manageAutoRenew', \Modules\Hr\Models\Contract::query()->find($contractId))
        : \Modules\Hr\Support\Employees\HrAccess::is(auth()->user(), 'HR');
@endphp

<section class="@container/form flex flex-col gap-5" data-contract-form>
    <x-ui.detail-header
        :back="BackUrl::for(route('hr.contracts.index'))"
        :breadcrumbs="[
            ['label' => __('hr_contracts.titles.index'), 'url' => route('hr.contracts.index')],
            ['label' => $title],
        ]"
        :title="$title"
    />

    <form wire:submit="save" novalidate class="flex flex-col gap-5">
        <x-ui.panel :title="__('hr_contracts.form.identity')">
            <div class="p-4 {{ $grid }}">
                <x-ui.form-select wire:model.live="form.employee_id" :label="__('hr_contracts.fields.employee_id')" :placeholder="__('common.select')" employees required :disabled="$contractId !== null || $renewalSourceId !== null" />
                <x-ui.form-select wire:model.live="programFilter" :label="__('common.program')" :placeholder="__('common.all_programs')">
                    <option value="">{{ __('common.all_programs') }}</option>
                    @foreach (Program::namesById() as $programId => $programName)
                        <option value="{{ $programId }}">{{ $programName }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="form.position_id" :label="__('hr_contracts.fields.position_id')" :placeholder="__('common.select')" required>
                    @foreach ($this->positionOptions as $positionId => $positionLabel)
                        <option value="{{ $positionId }}">{{ $positionLabel }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model.live="form.contract_type_id" :label="__('hr_contracts.fields.contract_type_id')" :placeholder="__('common.select')" required>
                    @foreach (ContractOptions::types() as $typeId => $typeName)
                        <option value="{{ $typeId }}">{{ $typeName }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="form.type" :label="__('hr_contracts.fields.type')" required>
                    @foreach ([ContractType::FullTime, ContractType::PartTime, ContractType::Freelance] as $kind)
                        @if (($category === 'service') === ($kind === ContractType::Freelance))
                            <option value="{{ $kind->value }}">{{ EnumLabel::of(ContractType::class, $kind->value) }}</option>
                        @endif
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model="form.place" :label="__('hr_contracts.fields.place')" required>
                    @foreach ([ContractPlace::Onsite, ContractPlace::Online] as $place)
                        <option value="{{ $place->value }}">{{ EnumLabel::of(ContractPlace::class, $place->value) }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-input wire:model="form.start_date" :label="__('hr_contracts.fields.start_date')" type="date" required />
                <x-ui.form-input wire:model="form.end_date" :label="__('hr_contracts.fields.end_date')" :help="__('hr_contracts.form.end_date_help')" type="date" required />
                <div class="flex flex-col justify-end gap-2">
                    <flux:checkbox wire:model="form.is_active" :label="__('hr_contracts.fields.is_active')" />
                    @if ($canAutoRenew && $category !== 'trial')
                        <flux:checkbox wire:model="form.auto_renew" :label="__('hr_contracts.fields.auto_renew')" />
                    @endif
                </div>
            </div>
        </x-ui.panel>

        @include('hr::pages.contracts._form-schedule', ['grid' => $grid])

        @include('hr::pages.contracts._form-pay', ['grid' => $grid])

        <div class="sticky bottom-0 z-10 flex flex-wrap items-center justify-end gap-2 border-t border-panel-border bg-white/90 px-4 py-3 backdrop-blur dark:bg-surface-1/90">
            <x-ui.button :href="BackUrl::for(route('hr.contracts.index'))" wire:navigate>{{ __('common.cancel') }}</x-ui.button>
            <x-ui.button type="submit" tone="primary" icon="check" wire:loading.attr="disabled" wire:target="save" data-test="save-contract">{{ __('hr_contracts.actions.save') }}</x-ui.button>
        </div>
    </form>
</section>
