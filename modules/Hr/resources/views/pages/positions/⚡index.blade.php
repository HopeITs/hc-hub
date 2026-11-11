<?php

use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Actions\Positions\DeletePosition;
use Modules\Hr\Actions\Positions\TogglePosition;
use Modules\Hr\Livewire\Concerns\Positions\ManagesPositionForm;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Employees\HrAccess;

new #[Layout('layouts::desk'), Title('hr_positions.titles.index')] class extends Component {
    use ManagesPositionForm;
    use WithPagination;

    private const FILTERS = ['search', 'status', 'city', 'program', 'department', 'seats'];

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'active')]
    public string $status = 'active';

    #[Url(except: '')]
    public string $city = '';

    #[Url(except: '')]
    public string $program = '';

    #[Url(except: '')]
    public string $department = '';

    /** '' every position, 'open' only positions with a free seat. */
    #[Url(except: '')]
    public string $seats = '';

    public bool $showConfirmModal = false;

    #[Locked]
    public string $confirming = '';

    #[Locked]
    public ?int $confirmingId = null;

    #[Locked]
    public string $confirmingName = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Position::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, self::FILTERS, true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'city', 'program', 'department', 'seats']);
        $this->resetPage();
    }

    #[Computed]
    public function positions(): LengthAwarePaginator
    {
        $cities = HrAccess::cityIds(Auth::user());

        return Position::query()
            ->select(['id', 'title_en', 'title_ar', 'program_id', 'city_id', 'center_id', 'department_id', 'job_level_id', 'direct_manager_id', 'max_emp', 'exist_emp', 'is_active'])
            ->with(['jobLevel:id,name_en,name_ar', 'directManager:id,title_en,title_ar'])
            ->when($cities !== null, fn ($query) => $query->inCity($cities))
            ->search($this->search)
            ->when($this->status !== 'all', fn ($query) => $query->where('positions.is_active', $this->status === 'active'))
            ->when($this->city !== '', fn ($query) => $query->inCity((int) $this->city))
            ->when($this->program !== '', fn ($query) => $query->where('positions.program_id', (int) $this->program))
            ->when($this->department !== '', fn ($query) => $query->where('positions.department_id', (int) $this->department))
            ->when($this->seats === 'open', fn ($query) => $query->whereColumn('positions.exist_emp', '<', 'positions.max_emp'))
            ->orderBy(app()->getLocale() === 'ar' ? 'title_ar' : 'title_en')
            ->paginate(25);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function cityOptions(): array
    {
        $allowed = HrAccess::cityIds(Auth::user());

        return array_filter(City::options(), fn (int $id): bool => $allowed === null || in_array($id, $allowed, true), ARRAY_FILTER_USE_KEY);
    }

    public function confirm(string $action, int $positionId): void
    {
        abort_unless(in_array($action, ['delete', 'toggle'], true), 404);

        $position = Position::query()->select(['id', 'title_en', 'title_ar', 'city_id', 'center_id', 'is_active'])->findOrFail($positionId);
        Gate::authorize($action === 'delete' ? 'delete' : 'update', $position);

        $this->confirming = $action;
        $this->confirmingId = $position->id;
        $this->confirmingName = (string) $position->title;
        $this->showConfirmModal = true;
    }

    public function cancelConfirm(): void
    {
        $this->showConfirmModal = false;
        $this->confirming = '';
        $this->confirmingId = null;
    }

    public function runConfirmed(): void
    {
        $position = Position::query()->findOrFail((int) $this->confirmingId);

        if ($this->confirming === 'delete') {
            $deleted = app(DeletePosition::class)->handle($position, Auth::user());
            Flux::toast(variant: $deleted ? 'success' : 'danger', text: $deleted ? __('hr_positions.toasts.deleted') : __('hr_positions.toasts.delete_blocked'));
        } else {
            app(TogglePosition::class)->handle($position, Auth::user());
            Flux::toast(variant: 'success', text: __('hr_positions.toasts.status_changed'));
        }

        $this->cancelConfirm();
        unset($this->positions);
    }
};
?>

@use('Modules\Core\Models\Center')

@php
    $positions = $this->positions;
    $hasFilters = $search !== '' || $city !== '' || $program !== '' || $department !== '' || $seats !== '';
@endphp

<x-desk.register class="admin-directory @container/directory" heading-id="hr-positions-heading" :title="__('hr_positions.titles.index')" :count="$positions->total()">
    <x-slot:search>
        <x-desk.register-search id="hr-position-search" :label="__('hr_positions.search')" :placeholder="__('hr_positions.search_placeholder')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select id="hr-position-status" :label="__('common.status')" wire:model.live="status" :value="$status">
            <option value="active">{{ __('hr_positions.status.active') }}</option>
            <option value="inactive">{{ __('hr_positions.status.inactive') }}</option>
            <option value="all">{{ __('hr_positions.status.all') }}</option>
        </x-ui.header-filter-select>
        <x-ui.header-filter-select id="hr-position-seats" :label="__('hr_positions.fields.seats')" wire:model.live="seats" :value="$seats">
            <option value="">{{ __('hr_positions.filters.any_seats') }}</option>
            <option value="open">{{ __('hr_positions.filters.open_seats') }}</option>
        </x-ui.header-filter-select>
        @if (count($this->cityOptions) > 1)
            <x-ui.header-filter-select id="hr-position-city" :label="__('common.city')" wire:model.live="city" :value="$city">
                <option value="">{{ __('hr_employees.filters.all_cities') }}</option>
                @foreach ($this->cityOptions as $cityId => $cityName)
                    <option value="{{ $cityId }}">{{ $cityName }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
        <x-ui.header-filter-select id="hr-position-program" :label="__('common.program')" wire:model.live="program" :value="$program">
            <option value="">{{ __('common.all_programs') }}</option>
            @foreach (Program::namesById() as $programId => $programName)
                <option value="{{ $programId }}">{{ $programName }}</option>
            @endforeach
        </x-ui.header-filter-select>
        <x-ui.header-filter-select id="hr-position-department" :label="__('hr_positions.fields.department_id')" wire:model.live="department" :value="$department">
            <option value="">{{ __('hr_employees.filters.all_departments') }}</option>
            @foreach (Department::options() as $departmentOption)
                <option value="{{ $departmentOption->id }}">{{ $departmentOption->name }}</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    <x-slot:actions>
        @can('positions.view')
            <x-ui.button icon="users" :href="route('hr.positions.team')" wire:navigate>{{ __('hr_positions.titles.team') }}</x-ui.button>
        @endcan
        @can('create', \Modules\Hr\Models\Position::class)
            <x-ui.button tone="primary" icon="plus" wire:click="openPosition" data-test="create-position" data-quick-create>{{ __('hr_positions.actions.add') }}</x-ui.button>
        @endcan
    </x-slot:actions>

    @if ($positions->isEmpty())
        <x-ui.empty-state icon="briefcase" :heading="__('hr_positions.empty.heading')" :body="$hasFilters ? __('common.change_the_search_or_filters_and_try_again') : __('hr_positions.empty.body')">
            @if ($hasFilters)
                <x-slot:action>
                    <x-ui.button tone="primary" icon="arrow-path" wire:click="clearFilters">{{ __('common.reset_filters') }}</x-ui.button>
                </x-slot:action>
            @endif
        </x-ui.empty-state>
    @else
        @php
            $muted = 'text-secondary-medium dark:text-ink-muted';
            $canUpdate = auth()->user()->can('positions.update');
            $canDelete = auth()->user()->can('positions.delete');
        @endphp
        <x-ui.data-table :caption="__('hr_positions.titles.index')">
            <x-slot:head>
                <th>{{ __('hr_positions.fields.title') }}</th>
                <th>{{ __('common.program') }}</th>
                <th>{{ __('hr_positions.fields.center_id') }}</th>
                <th class="w-40!">{{ __('hr_positions.fields.job_level_id') }}</th>
                <th class="w-28!">{{ __('hr_positions.fields.seats') }}</th>
                <th class="w-32!"><span class="sr-only">{{ __('common.actions') }}</span></th>
            </x-slot:head>

            @foreach ($positions as $position)
                <tr wire:key="position-{{ $position->id }}" data-position="{{ $position->id }}" @class(['opacity-60' => ! $position->is_active])>
                    <td>
                        <span class="block font-semibold" dir="auto">{{ $position->title }}</span>
                        <span class="block text-xs {{ $muted }}">{{ Department::nameOf((int) $position->department_id) }}@if ($position->directManager) · {{ __('hr_positions.reports_to', ['title' => $position->directManager->title]) }}@endif</span>
                    </td>
                    <td>{{ Program::nameOf($position->program_id) }}</td>
                    <td>
                        <span class="block">{{ Center::nameOf($position->center_id) ?: '—' }}</span>
                        <span class="block text-xs {{ $muted }}">{{ City::nameOf($position->city_id) }}</span>
                    </td>
                    <td>{{ $position->jobLevel?->name ?? '—' }}</td>
                    <td class="tabular-nums">
                        <span @class(['font-semibold', 'text-status-critical' => $position->exist_emp >= $position->max_emp])>{{ (int) $position->exist_emp }} / {{ (int) $position->max_emp }}</span>
                    </td>
                    <td>
                        <span class="flex items-center justify-end gap-1">
                            @if ($canUpdate)
                                <x-ui.button tone="ghost" size="sm" icon="pencil-square" wire:click="openPosition({{ $position->id }})" :aria-label="__('common.edit_name', ['name' => $position->title])" />
                                <x-ui.button tone="ghost" size="sm" :icon="$position->is_active ? 'pause' : 'play'" wire:click="confirm('toggle', {{ $position->id }})" :aria-label="__('hr_positions.actions.toggle_name', ['name' => $position->title])" />
                            @endif
                            @if ($canDelete)
                                <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="confirm('delete', {{ $position->id }})" :aria-label="__('common.delete_name', ['name' => $position->title])" />
                            @endif
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-3">
            <flux:pagination :paginator="$positions" scroll-to="#hr-positions-heading" />
        </div>
    @endif

    @include('hr::pages.positions._form-modal')

    <x-ui.confirm-modal
        wire:model="showConfirmModal"
        close="cancelConfirm"
        :tone="$confirming === 'delete' ? 'danger' : 'neutral'"
        :title="$confirming === 'delete' ? __('hr_positions.confirm.delete_title', ['name' => $confirmingName]) : __('hr_positions.confirm.toggle_title', ['name' => $confirmingName])"
        :message="$confirming === 'delete' ? __('hr_positions.confirm.delete_body') : __('hr_positions.confirm.toggle_body')"
        confirm="runConfirmed"
        :confirm-label="$confirming === 'delete' ? __('common.delete') : __('common.confirm')"
        confirm-test="confirm-position-action"
    />
</x-desk.register>
