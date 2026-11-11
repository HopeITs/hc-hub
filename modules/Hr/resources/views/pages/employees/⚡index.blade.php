<?php

use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Actions\Employees\DeleteEmployee;
use Modules\Hr\Actions\Employees\ToggleEmployeeStatus;
use Modules\Hr\Livewire\Concerns\Employees\ReadsEmployeeDirectory;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\HrAccess;

new #[Layout('layouts::desk'), Title('hr_employees.titles.index')] class extends Component {
    use ReadsEmployeeDirectory;
    use WithPagination;

    private const FILTERS = ['search', 'view', 'city', 'program', 'department'];

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'active')]
    public string $view = 'active';

    #[Url(except: '')]
    public string $city = '';

    #[Url(except: '')]
    public string $program = '';

    #[Url(except: '')]
    public string $department = '';

    public bool $showConfirmModal = false;

    /** The action the confirmation dialog is about: delete or toggle. */
    #[Locked]
    public string $confirming = '';

    #[Locked]
    public ?int $confirmingId = null;

    #[Locked]
    public string $confirmingName = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Employee::class);

        $this->normaliseFilters();
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::FILTERS, true)) {
            $this->normaliseFilters();
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'city', 'program', 'department']);
        $this->resetPage();
    }

    public function confirm(string $action, int $employeeId): void
    {
        abort_unless(in_array($action, ['delete', 'toggle'], true), 404);

        $employee = $this->scopedEmployee($employeeId);

        Gate::authorize($action === 'delete' ? 'delete' : 'update', $employee);

        $this->confirming = $action;
        $this->confirmingId = $employee->id;
        $this->confirmingName = $employee->name;
        $this->showConfirmModal = true;
    }

    public function cancelConfirm(): void
    {
        $this->showConfirmModal = false;
        $this->confirming = '';
        $this->confirmingId = null;
        $this->confirmingName = '';
    }

    public function runConfirmed(): void
    {
        if ($this->confirmingId === null) {
            $this->cancelConfirm();

            return;
        }

        $employee = $this->scopedEmployee($this->confirmingId);

        if ($this->confirming === 'delete') {
            $deleted = app(DeleteEmployee::class)->handle($employee);

            Flux::toast(
                variant: $deleted ? 'success' : 'danger',
                text: $deleted ? __('hr_employees.toasts.deleted') : __('hr_employees.toasts.delete_blocked'),
            );
        } else {
            app(ToggleEmployeeStatus::class)->handle($employee);

            Flux::toast(variant: 'success', text: __('hr_employees.toasts.status_changed'));
        }

        $this->cancelConfirm();
        unset($this->employees, $this->viewCounts);
    }

    protected function scopedEmployee(int $employeeId): Employee
    {
        return Employee::query()
            ->forCity(HrAccess::cityIds(auth()->user()))
            ->withExists(['realContracts as has_real_contract'])
            ->findOrFail($employeeId);
    }

    protected function normaliseFilters(): void
    {
        $this->view = $this->effectiveView();

        if ($this->city !== '' && ! array_key_exists((int) $this->city, $this->cityOptions)) {
            $this->city = '';
        }

        if ($this->program !== '' && ! array_key_exists((int) $this->program, $this->programOptions)) {
            $this->program = '';
        }

        if ($this->department !== '' && ! array_key_exists((int) $this->department, $this->departmentOptions)) {
            $this->department = '';
        }
    }
};
?>

@php
    $employees = $this->employees;
    $counts = $this->viewCounts;
    $hasFilters = $search !== '' || $city !== '' || $program !== '' || $department !== '';
@endphp

<x-desk.register class="admin-directory @container/directory" heading-id="hr-employees-heading" :title="__('hr_employees.titles.index')" :count="$employees->total()">
    <x-slot:figures>
        @foreach ($counts as $viewKey => $viewCount)
            <span data-figure="{{ $viewKey }}">{{ __('hr_employees.views.'.$viewKey) }} <b class="dk-num">{{ $viewCount }}</b></span>@unless ($loop->last) · @endunless
        @endforeach
    </x-slot:figures>

    <x-slot:search>
        <x-desk.register-search id="hr-employee-search" :label="__('hr_employees.search')" :placeholder="__('hr_employees.search_placeholder')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select id="hr-employee-view" :label="__('common.status')" wire:model.live="view" :value="$this->effectiveView()">
            @foreach ($counts as $viewKey => $viewCount)
                <option value="{{ $viewKey }}">{{ __('hr_employees.views.'.$viewKey) }}</option>
            @endforeach
        </x-ui.header-filter-select>

        @if (count($this->cityOptions) > 1)
            <x-ui.header-filter-select id="hr-employee-city" :label="__('common.city')" wire:model.live="city" :value="$city">
                <option value="">{{ __('hr_employees.filters.all_cities') }}</option>
                @foreach ($this->cityOptions as $cityId => $cityName)
                    <option value="{{ $cityId }}">{{ $cityName }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif

        <x-ui.header-filter-select id="hr-employee-program" :label="__('common.program')" wire:model.live="program" :value="$program">
            <option value="">{{ __('common.all_programs') }}</option>
            @foreach ($this->programOptions as $programId => $programName)
                <option value="{{ $programId }}">{{ $programName }}</option>
            @endforeach
        </x-ui.header-filter-select>

        <x-ui.header-filter-select id="hr-employee-department" :label="__('hr_employees.fields.department')" wire:model.live="department" :value="$department">
            <option value="">{{ __('hr_employees.filters.all_departments') }}</option>
            @foreach ($this->departmentOptions as $departmentId => $departmentName)
                <option value="{{ $departmentId }}">{{ $departmentName }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    <x-slot:actions>
        @can('employees.export')
            <x-ui.button tone="action" icon="arrow-down-tray" wire:click="exportCsv" wire:loading.attr="disabled" wire:target="exportCsv" data-test="export-employees">{{ __('hr_employees.actions.export') }}</x-ui.button>
        @endcan
        @can('employees.create')
            <x-ui.button tone="primary" icon="plus" :href="route('hr.employees.create')" wire:navigate data-test="create-employee-button" data-quick-create>{{ __('hr_employees.actions.add') }}</x-ui.button>
        @endcan
    </x-slot:actions>

    @include('hr::pages.employees._index-table', ['employees' => $employees, 'hasFilters' => $hasFilters])

    <x-ui.confirm-modal
        wire:model="showConfirmModal"
        close="cancelConfirm"
        :tone="$confirming === 'delete' ? 'danger' : 'neutral'"
        :title="$confirming === 'delete' ? __('hr_employees.confirm.delete_title', ['name' => $confirmingName]) : __('hr_employees.confirm.toggle_title', ['name' => $confirmingName])"
        :message="$confirming === 'delete' ? __('hr_employees.confirm.delete_body') : __('hr_employees.confirm.toggle_body')"
        confirm="runConfirmed"
        :confirm-label="$confirming === 'delete' ? __('common.delete') : __('common.confirm')"
        confirm-test="confirm-employee-action"
    />
</x-desk.register>
