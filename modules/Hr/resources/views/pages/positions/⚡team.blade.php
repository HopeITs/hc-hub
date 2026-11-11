<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Employees\HrAccess;

/*
 * The team chart: positions drawn under their direct manager, each with the
 * people holding it and the seats still open. Opens on the viewer's own team
 * when they hold a managing position; HR and senior roles can widen it to a
 * program or a city. Replaces HR's org chart and the manager's staffing canvas.
 */
new #[Layout('layouts::desk'), Title('hr_positions.titles.team')] class extends Component {
    #[Url(except: '')]
    public string $scope = '';

    #[Url(except: '')]
    public string $city = '';

    #[Url(except: '')]
    public string $program = '';

    #[Url(except: false)]
    public bool $openOnly = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Position::class);

        if ($this->scope === '' && $this->myPositionIds() !== []) {
            $this->scope = 'mine';
        }
    }

    /**
     * @return list<int>
     */
    public function myPositionIds(): array
    {
        $employeeId = Auth::user()?->employee_id;

        if ($employeeId === null) {
            return [];
        }

        return once(fn (): array => Contract::query()
            ->activeNow()
            ->where('employee_id', $employeeId)
            ->where('type', '!=', ContractTypeEnum::Draft->value)
            ->pluck('position_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
    }

    /**
     * The positions in view and who holds them.
     *
     * @return array{positions: \Illuminate\Support\Collection<int, Position>, children: array<int, list<int>>, roots: list<int>, holders: array<int, list<array{name: string, username: ?string}>>}
     */
    #[Computed]
    public function chart(): array
    {
        $cities = HrAccess::cityIds(Auth::user());

        $positions = Position::query()
            ->select(['id', 'title_en', 'title_ar', 'program_id', 'city_id', 'department_id', 'direct_manager_id', 'max_emp', 'exist_emp'])
            ->where('is_active', true)
            ->when($cities !== null && $this->scope !== 'mine', fn ($query) => $query->inCity($cities))
            ->when($this->city !== '' && $this->scope !== 'mine', fn ($query) => $query->inCity((int) $this->city))
            ->when($this->program !== '' && $this->scope !== 'mine', fn ($query) => $query->where('program_id', (int) $this->program))
            ->limit(2000)
            ->get()
            ->keyBy('id');

        $children = [];

        foreach ($positions as $position) {
            if ($position->direct_manager_id !== null && $positions->has($position->direct_manager_id)) {
                $children[$position->direct_manager_id][] = $position->id;
            }
        }

        $roots = $this->scope === 'mine'
            ? array_values(array_filter($this->myPositionIds(), fn (int $id): bool => $positions->has($id)))
            : $positions->filter(fn (Position $position): bool => $position->direct_manager_id === null || ! $positions->has($position->direct_manager_id))->keys()->all();

        if ($this->scope === 'mine') {
            $keep = [];
            $walk = function (int $id) use (&$walk, &$keep, $children): void {
                $keep[$id] = true;
                foreach ($children[$id] ?? [] as $child) {
                    if (! isset($keep[$child])) {
                        $walk($child);
                    }
                }
            };
            foreach ($roots as $root) {
                $walk($root);
            }
            $positions = $positions->filter(fn (Position $position, int $id): bool => isset($keep[$id]));
        }

        if ($this->openOnly) {
            $keep = [];
            $walk = function (int $id) use (&$walk, &$keep, $children, $positions): bool {
                $position = $positions->get($id);
                $open = $position !== null && $position->exist_emp < $position->max_emp;
                foreach ($children[$id] ?? [] as $child) {
                    $open = $walk($child) || $open;
                }
                if ($open) {
                    $keep[$id] = true;
                }

                return $open;
            };
            foreach ($roots as $root) {
                $walk($root);
            }
            $positions = $positions->filter(fn (Position $position, int $id): bool => isset($keep[$id]));
            $roots = array_values(array_filter($roots, fn (int $id): bool => isset($keep[$id])));
        }

        $holders = [];

        Contract::query()
            ->activeNow()
            ->where('type', '!=', ContractTypeEnum::Draft->value)
            ->whereIn('position_id', $positions->keys())
            ->select(['id', 'employee_id', 'position_id'])
            ->with('employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar,username')
            ->get()
            ->each(function (Contract $contract) use (&$holders): void {
                $holders[$contract->position_id][] = ['name' => (string) $contract->employee?->name, 'username' => $contract->employee?->username];
            });

        return ['positions' => $positions, 'children' => $children, 'roots' => $roots, 'holders' => $holders];
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
};
?>

@php
    $chart = $this->chart;
    $positions = $chart['positions'];
    $openSeats = $positions->sum(fn ($position) => max(0, (int) $position->max_emp - (int) $position->exist_emp));
@endphp

<x-desk.register class="@container/directory" heading-id="hr-team-heading" :title="__('hr_positions.titles.team')" :count="$positions->count()">
    <x-slot:figures>
        <span>{{ __('hr_positions.team.positions') }} <b class="dk-num">{{ $positions->count() }}</b></span> ·
        <span>{{ __('hr_positions.team.open_seats') }} <b class="dk-num">{{ $openSeats }}</b></span>
    </x-slot:figures>

    <x-slot:filters>
        <x-ui.header-filter-select id="hr-team-scope" :label="__('hr_positions.team.scope')" wire:model.live="scope" :value="$scope">
            @if ($this->myPositionIds() !== [])
                <option value="mine">{{ __('hr_positions.team.my_team') }}</option>
            @endif
            <option value="">{{ __('hr_positions.team.whole') }}</option>
        </x-ui.header-filter-select>
        @if ($scope !== 'mine')
            @if (count($this->cityOptions) > 1)
                <x-ui.header-filter-select id="hr-team-city" :label="__('common.city')" wire:model.live="city" :value="$city">
                    <option value="">{{ __('hr_employees.filters.all_cities') }}</option>
                    @foreach ($this->cityOptions as $cityId => $cityName)
                        <option value="{{ $cityId }}">{{ $cityName }}</option>
                    @endforeach
                </x-ui.header-filter-select>
            @endif
            <x-ui.header-filter-select id="hr-team-program" :label="__('common.program')" wire:model.live="program" :value="$program">
                <option value="">{{ __('common.all_programs') }}</option>
                @foreach (Program::namesById() as $programId => $programName)
                    <option value="{{ $programId }}">{{ $programName }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
    </x-slot:filters>

    <x-slot:actions>
        <flux:checkbox wire:model.live="openOnly" :label="__('hr_positions.team.open_only')" />
        @can('viewAny', \Modules\Hr\Models\Position::class)
            <x-ui.button icon="briefcase" :href="route('hr.positions.index')" wire:navigate>{{ __('hr_positions.titles.index') }}</x-ui.button>
        @endcan
    </x-slot:actions>

    @if ($chart['roots'] === [])
        <x-ui.empty-state icon="users" :heading="__('hr_positions.team.empty_heading')" :body="__('hr_positions.team.empty_body')" />
    @else
        <ul class="flex flex-col gap-2" role="tree" data-team-chart>
            @foreach ($chart['roots'] as $rootId)
                @include('hr::pages.positions._team-node', ['nodeId' => $rootId, 'depth' => 0])
            @endforeach
        </ul>
    @endif
</x-desk.register>
