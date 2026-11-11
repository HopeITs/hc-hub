<?php

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Employees\HrAccess;

new #[Layout('layouts::desk'), Title('hr_contracts.titles.index')] class extends Component {
    use WithPagination;

    public const VIEWS = ['active', 'ending-soon', 'ended', 'not-renewed', 'draft'];

    private const FILTERS = ['search', 'view', 'city', 'program', 'type'];

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'active')]
    public string $view = 'active';

    #[Url(except: '')]
    public string $city = '';

    #[Url(except: '')]
    public string $program = '';

    #[Url(except: '')]
    public string $type = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Contract::class);

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
        $this->reset(['search', 'city', 'program', 'type']);
        $this->resetPage();
    }

    #[Computed]
    public function contracts(): LengthAwarePaginator
    {
        return $this->filteredQuery()
            ->inView($this->view)
            ->select(['contracts.id', 'contracts.employee_id', 'contracts.position_id', 'contracts.contract_type_id', 'contracts.type', 'contracts.start_date', 'contracts.end_date', 'contracts.terminated_at', 'contracts.termination_reason_id', 'contracts.is_active', 'contracts.auto_renew'])
            ->with([
                'employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar,username,city_id',
                'position:id,title_en,title_ar,program_id',
            ])
            ->orderBy($this->view === 'ending-soon' ? 'contracts.end_date' : 'contracts.start_date', $this->view === 'ending-soon' ? 'asc' : 'desc')
            ->orderByDesc('contracts.id')
            ->paginate(25);
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function viewCounts(): array
    {
        $counts = [];

        foreach (self::VIEWS as $view) {
            $counts[$view] = $this->filteredQuery()->inView($view)->count();
        }

        return $counts;
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function cityOptions(): array
    {
        $allowed = HrAccess::cityIds(Auth::user());

        return array_filter(City::options(withNational: false), fn (int $id): bool => $allowed === null || in_array($id, $allowed, true), ARRAY_FILTER_USE_KEY);
    }

    /**
     * @return Builder<Contract>
     */
    protected function filteredQuery(): Builder
    {
        return Contract::query()
            ->visibleTo(Auth::user())
            ->search($this->search)
            ->when($this->city !== '', fn (Builder $query) => $query->whereHas('employee', fn (Builder $employee) => $employee->where('city_id', (int) $this->city)))
            ->when($this->program !== '', fn (Builder $query) => $query->whereHas('position', fn (Builder $position) => $position->where('program_id', (int) $this->program)))
            ->when($this->type !== '', fn (Builder $query) => $query->where('contracts.contract_type_id', (int) $this->type));
    }

    protected function normaliseFilters(): void
    {
        if (! in_array($this->view, self::VIEWS, true)) {
            $this->view = 'active';
        }

        if ($this->city !== '' && ! array_key_exists((int) $this->city, $this->cityOptions)) {
            $this->city = '';
        }

        if ($this->program !== '' && ! array_key_exists((int) $this->program, Program::namesById())) {
            $this->program = '';
        }
    }
};
?>

@use('Modules\Hr\Support\Contracts\ContractOptions')

@php
    $contracts = $this->contracts;
    $hasFilters = $search !== '' || $city !== '' || $program !== '' || $type !== '';
@endphp

<x-desk.register class="admin-directory @container/directory" heading-id="hr-contracts-heading" :title="__('hr_contracts.titles.index')" :count="$contracts->total()">
    <x-slot:figures>
        @foreach ($this->viewCounts as $viewKey => $viewCount)
            <span data-figure="{{ $viewKey }}">{{ __('hr_contracts.views.'.$viewKey) }} <b class="dk-num">{{ $viewCount }}</b></span>@unless ($loop->last) · @endunless
        @endforeach
    </x-slot:figures>

    <x-slot:search>
        <x-desk.register-search id="hr-contract-search" :label="__('hr_contracts.search')" :placeholder="__('hr_employees.search_placeholder')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select id="hr-contract-view" :label="__('common.status')" wire:model.live="view" :value="$view">
            @foreach ($this::VIEWS as $viewKey)
                <option value="{{ $viewKey }}">{{ __('hr_contracts.views.'.$viewKey) }}</option>
            @endforeach
        </x-ui.header-filter-select>

        @if (count($this->cityOptions) > 1)
            <x-ui.header-filter-select id="hr-contract-city" :label="__('common.city')" wire:model.live="city" :value="$city">
                <option value="">{{ __('hr_employees.filters.all_cities') }}</option>
                @foreach ($this->cityOptions as $cityId => $cityName)
                    <option value="{{ $cityId }}">{{ $cityName }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif

        <x-ui.header-filter-select id="hr-contract-program" :label="__('common.program')" wire:model.live="program" :value="$program">
            <option value="">{{ __('common.all_programs') }}</option>
            @foreach (Program::namesById() as $programId => $programName)
                <option value="{{ $programId }}">{{ $programName }}</option>
            @endforeach
        </x-ui.header-filter-select>

        <x-ui.header-filter-select id="hr-contract-type" :label="__('hr_contracts.fields.contract_type_id')" wire:model.live="type" :value="$type">
            <option value="">{{ __('hr_contracts.filters.all_types') }}</option>
            @foreach (ContractOptions::types() as $typeId => $typeName)
                <option value="{{ $typeId }}">{{ $typeName }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    <x-slot:actions>
        @can('create', \Modules\Hr\Models\Contract::class)
            <x-ui.button tone="primary" icon="plus" :href="route('hr.contracts.create')" wire:navigate data-test="create-contract" data-quick-create>{{ __('hr_contracts.actions.add') }}</x-ui.button>
        @endcan
    </x-slot:actions>

    @include('hr::pages.contracts._index-table', ['contracts' => $contracts, 'hasFilters' => $hasFilters])
</x-desk.register>
