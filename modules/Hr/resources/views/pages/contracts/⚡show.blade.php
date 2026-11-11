<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Contracts\DeleteContract;
use Modules\Hr\Actions\Contracts\TerminateContract;
use Modules\Hr\Actions\Contracts\ToggleAutoRenew;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Employees\HrAccess;

new #[Layout('layouts::desk'), Title('hr_contracts.titles.show')] class extends Component {
    #[Locked]
    public int $contractId;

    #[Locked]
    public bool $canViewPay = false;

    public bool $showTerminateModal = false;

    public bool $showDeleteModal = false;

    /** @var array{terminated_at: string, termination_reason_id: string, reason_of_termination: string} */
    public array $termination = ['terminated_at' => '', 'termination_reason_id' => '', 'reason_of_termination' => ''];

    public function mount(Contract $contract): void
    {
        Gate::authorize('view', $contract);

        $this->contractId = $contract->id;
        $this->canViewPay = HrAccess::canViewSalary(Auth::user());
    }

    #[Computed]
    public function contract(): Contract
    {
        $columns = ['id', 'employee_id', 'position_id', 'contract_type_id', 'type', 'place', 'start_date', 'end_date', 'terminated_at',
            'termination_reason_id', 'reason_of_termination', 'is_active', 'auto_renew', 'weekly_hours', 'schedule_mode',
            'work_hours_from', 'work_hours_to', 'work_hours_2_from', 'work_hours_2_to', 'work_day_from', 'work_day_to',
            'flex_days_per_week', 'flex_hours_per_week', 'flex_hours_per_month', 'flex_varies_by_requirements',
            'flex_coordinated_with_manager', 'flex_custom_en', 'flex_custom_ar', 'created_at', 'updated_at'];

        if ($this->canViewPay) {
            $columns = [...$columns, 'pay_mode', 'currency', 'salary', 'salary_per', 'session_duration'];
        }

        return Contract::query()
            ->select($columns)
            ->with([
                'employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar,username,city_id',
                'position:id,title_en,title_ar,program_id,center_id,department_id',
            ])
            ->when($this->canViewPay, fn ($query) => $query->with(['payTiers' => fn ($tiers) => $tiers->select(['id', 'contract_id', 'condition_en', 'condition_ar', 'amount', 'sort_order'])->orderBy('sort_order')]))
            ->findOrFail($this->contractId);
    }

    public function toggleAutoRenew(): void
    {
        $contract = $this->contract;

        app(ToggleAutoRenew::class)->handle($contract, ! $contract->auto_renew, Auth::user());

        unset($this->contract);
        Flux::toast(variant: 'success', text: __('hr_contracts.toasts.auto_renew_changed'));
    }

    public function openTerminate(): void
    {
        Gate::authorize('update', $this->contract);

        $this->resetValidation();
        $this->termination = ['terminated_at' => today()->toDateString(), 'termination_reason_id' => '', 'reason_of_termination' => ''];
        $this->showTerminateModal = true;
    }

    public function closeTerminate(): void
    {
        $this->showTerminateModal = false;
    }

    public function terminate(): void
    {
        try {
            app(TerminateContract::class)->handle(Contract::query()->findOrFail($this->contractId), $this->termination, Auth::user());
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw \Illuminate\Validation\ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $key): array => ['termination.'.$key => $messages])->all()
            );
        }

        $this->showTerminateModal = false;
        unset($this->contract);
        Flux::toast(variant: 'success', text: __('hr_contracts.toasts.terminated'));
    }

    public function confirmDelete(): void
    {
        Gate::authorize('delete', $this->contract);

        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        $deleted = app(DeleteContract::class)->handle(Contract::query()->findOrFail($this->contractId), Auth::user());

        $this->showDeleteModal = false;

        if (! $deleted) {
            Flux::toast(variant: 'danger', text: __('hr_contracts.toasts.delete_blocked'));

            return;
        }

        Flux::toast(variant: 'success', text: __('hr_contracts.toasts.deleted'));
        $this->redirectRoute('hr.contracts.index', navigate: true);
    }
};
?>

@use('App\Support\BackUrl')
@use('Modules\Core\Models\City')
@use('Modules\Core\Models\Center')
@use('Modules\Core\Models\Program')
@use('Modules\Hr\Enums\ContractPlace')
@use('Modules\Hr\Enums\ContractStatus')
@use('Modules\Hr\Enums\ContractType')
@use('Modules\Hr\Support\Contracts\ContractOptions')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $contract = $this->contract;
    $employee = $contract->employee;
    $status = ContractStatus::for($contract);
    $date = fn (?\DateTimeInterface $value): string => $value?->translatedFormat(__('formats.date')) ?? '—';
    $title = $employee?->name ?? __('hr_contracts.titles.show');
    $user = auth()->user();
@endphp

<section class="@container/record flex flex-col gap-5" data-contract="{{ $contract->id }}">
    <x-ui.detail-header
        :back="BackUrl::for(route('hr.contracts.index'))"
        :breadcrumbs="[
            ['label' => __('hr_contracts.titles.index'), 'url' => route('hr.contracts.index')],
            ['label' => $title],
        ]"
        :title="$title"
        :subtitle="$contract->position?->title"
    >
        <x-slot:meta>
            <x-hr::contracts.status-badge :status="$status" />
            <span>{{ ContractOptions::typeName($contract->contract_type_id) }}</span>
            <span class="tabular-nums">{{ $date($contract->start_date) }} – {{ $date($contract->end_date) }}</span>
        </x-slot:meta>

        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @if ($employee?->username)
                    <x-ui.button icon="user" :href="route('hr.employees.show', $employee->username)" wire:navigate>{{ __('hr_contracts.actions.open_employee') }}</x-ui.button>
                @endif
                @can('export', $contract)
                    <x-ui.button icon="printer" :href="route('hr.contracts.print', $contract->id)" target="_blank" data-test="print-contract">{{ __('hr_contracts.actions.print') }}</x-ui.button>
                @endcan
                @can('update', $contract)
                    <x-ui.button icon="pencil-square" :href="route('hr.contracts.edit', $contract->id)" wire:navigate>{{ __('hr_contracts.actions.edit') }}</x-ui.button>
                    @unless ($contract->isTerminated() || $status === ContractStatus::Draft)
                        <x-ui.button icon="no-symbol" wire:click="openTerminate" data-test="terminate-contract">{{ __('hr_contracts.actions.terminate') }}</x-ui.button>
                    @endunless
                @endcan
                @can('renew', $contract)
                    <x-ui.button tone="primary" icon="arrow-path" :href="route('hr.contracts.renew', $contract->id)" wire:navigate data-test="renew-contract">{{ __('hr_contracts.actions.renew') }}</x-ui.button>
                @endcan
                @can('delete', $contract)
                    <x-ui.button tone="ghost" icon="trash" wire:click="confirmDelete" :aria-label="__('common.delete')" />
                @endcan
            </div>
        </x-slot:actions>
    </x-ui.detail-header>

    <div class="grid gap-5 @4xl/record:grid-cols-2">
        <x-ui.panel :title="__('hr_contracts.form.identity')">
            <dl class="grid gap-4 p-4 @2xl/record:grid-cols-2">
                <x-hr::employees.fact :label="__('hr_contracts.fields.position_id')" :value="$contract->position?->title" />
                <x-hr::employees.fact :label="__('common.program')" :value="$contract->position ? Program::nameOf($contract->position->program_id) : null" />
                <x-hr::employees.fact :label="__('hr_employees.fields.center')" :value="Center::nameOf($contract->position?->center_id)" />
                <x-hr::employees.fact :label="__('common.city')" :value="City::nameOf($employee?->city_id)" />
                <x-hr::employees.fact :label="__('hr_contracts.fields.type')" :value="EnumLabel::of(ContractType::class, $contract->type)" />
                <x-hr::employees.fact :label="__('hr_contracts.fields.place')" :value="EnumLabel::of(ContractPlace::class, $contract->place)" />
                <x-hr::employees.fact :label="__('hr_contracts.fields.start_date')" :value="$date($contract->start_date)" />
                <x-hr::employees.fact :label="__('hr_contracts.fields.end_date')" :value="$date($contract->end_date)" />
                <x-hr::employees.fact :label="__('hr_contracts.fields.auto_renew')">
                    <span class="flex items-center gap-2">
                        {{ $contract->auto_renew ? __('hr_contracts.yes') : __('hr_contracts.no') }}
                        @can('manageAutoRenew', $contract)
                            @if ($contract->auto_renew || $contract->canAutoRenew())
                                <x-ui.button size="sm" wire:click="toggleAutoRenew" data-test="toggle-auto-renew">{{ $contract->auto_renew ? __('hr_contracts.actions.stop_auto_renew') : __('hr_contracts.actions.start_auto_renew') }}</x-ui.button>
                            @endif
                        @endcan
                    </span>
                    @error('autoRenew') <span class="block text-xs text-status-critical">{{ $message }}</span> @enderror
                </x-hr::employees.fact>
                @if ($contract->isTerminated())
                    <x-hr::employees.fact :label="__('hr_contracts.fields.terminated_at')" :value="$date($contract->terminated_at)" />
                    <x-hr::employees.fact :label="__('hr_contracts.fields.termination_reason_id')" :value="ContractOptions::terminationReasons()[$contract->termination_reason_id] ?? null" />
                    <x-hr::employees.fact class="@2xl/record:col-span-2" :label="__('hr_contracts.fields.reason_of_termination')" :value="$contract->reason_of_termination" />
                @endif
            </dl>
        </x-ui.panel>

        @include('hr::pages.contracts._show-terms')
    </div>

    <x-ui.modal wire:model="showTerminateModal" size="md" :title="__('hr_contracts.actions.terminate')" submit="terminate" close="closeTerminate">
        @if ($showTerminateModal)
            <div class="flex flex-col gap-3">
                <x-ui.form-input wire:model="termination.terminated_at" :label="__('hr_contracts.fields.terminated_at')" type="date" required />
                <x-ui.form-select wire:model="termination.termination_reason_id" :label="__('hr_contracts.fields.termination_reason_id')" :placeholder="__('common.select')" required>
                    @foreach (ContractOptions::terminationReasons() as $reasonId => $reasonTitle)
                        <option value="{{ $reasonId }}">{{ $reasonTitle }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-textarea wire:model="termination.reason_of_termination" :label="__('hr_contracts.fields.reason_of_termination')" rows="3" maxlength="2000" />
            </div>
        @endif
    </x-ui.modal>

    <x-ui.confirm-modal
        wire:model="showDeleteModal"
        close="cancelDelete"
        :title="__('hr_contracts.confirm.delete_title')"
        :message="__('hr_contracts.confirm.delete_body')"
        confirm="delete"
        :confirm-label="__('common.delete')"
        confirm-test="confirm-contract-delete"
    />
</section>
