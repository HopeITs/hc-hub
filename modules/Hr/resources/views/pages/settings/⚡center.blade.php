<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Core\Models\Center;
use Modules\Core\Models\Program;
use Modules\Hr\Livewire\Concerns\Settings\ListsAssignedEmployees;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Support\Settings\HrAccess;
use Modules\Hr\Support\Settings\OrgCounts;

/*
 * One center as HR sees it: where it is, when it is open, how many people
 * work there now by contract type and by program, and who they are.
 * Read-only. A regional HR role cannot open another governorate's center.
 */
new #[Layout('layouts::desk'), Title('hr_settings.titles.center')] class extends Component {
    use ListsAssignedEmployees;
    use WithPagination;

    #[Locked]
    public int $centerId;

    public function mount(Center $center): void
    {
        Gate::authorize('centers.view');

        // Route binding resolves any id: without this a regional user could read another governorate's center.
        $allowed = HrAccess::for(Auth::user())->cityIds();
        abort_if($allowed !== null && ! in_array((int) $center->city_id, $allowed, true), 403);

        $this->centerId = $center->id;
    }

    protected function assignmentScope(): array
    {
        return ['center_id' => $this->centerId];
    }

    #[Computed]
    public function center(): Center
    {
        return Center::query()->with('city:id,name_en,name_ar')->findOrFail($this->centerId);
    }

    /** @return array{positions: int, seats: int, employees: int, assignments: int} */
    #[Computed]
    public function totals(): array
    {
        $scope = $this->assignmentScope();
        $counts = OrgCounts::positions('center_id', null, $scope)->get($this->centerId);

        return [
            'positions' => $counts->positions ?? 0,
            'seats' => $counts->seats ?? 0,
            'employees' => OrgCounts::headcount(null, $scope),
            'assignments' => (int) OrgCounts::activeAssignments(null, $scope)->toBase()->count(),
        ];
    }

    /**
     * People working here now per contract type: name => count.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function byContractType(): array
    {
        $counts = OrgCounts::activeAssignments(null, $this->assignmentScope())
            ->toBase()
            ->whereNotNull('contracts.contract_type_id')
            ->groupBy('contracts.contract_type_id')
            ->selectRaw('contracts.contract_type_id as type_id, count(distinct contracts.employee_id) as employees')
            ->pluck('employees', 'type_id');

        $types = ContractType::query()->select(['id', 'name_en', 'name_ar'])->whereIn('id', $counts->keys())->get()->keyBy('id');

        return $counts
            ->mapWithKeys(fn (mixed $employees, mixed $typeId): array => [(string) ($types->get((int) $typeId)?->name ?? '#'.$typeId) => (int) $employees])
            ->all();
    }

    /**
     * People working here now per program: name => count.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function byProgram(): array
    {
        return OrgCounts::employees('program_id', null, $this->assignmentScope())
            ->mapWithKeys(fn (int $employees, int $programId): array => [Program::nameOf($programId) ?: '#'.$programId => $employees])
            ->sortKeys()
            ->all();
    }
};
?>

@php
    $center = $this->center;
    $totals = $this->totals;
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $arabic = app()->getLocale() === 'ar';
    $address = trim((string) ($arabic ? ($center->address_ar ?: $center->address_en) : ($center->address_en ?: $center->address_ar)));
    $hours = OrgCounts::hours($center->open_at, $center->close_at);
    $weekend = OrgCounts::weekendDays($center->weekends);
    $facts = [
        __('common.city') => $center->city?->name ?? '',
        __('hr_settings.centers.hours') => $hours,
        __('hr_settings.centers.weekend') => $weekend,
        __('hr_settings.centers.address') => $address,
    ];
@endphp

<section class="flex flex-col gap-5" data-hr-center="{{ $center->id }}">
    <x-ui.detail-header
        :back="route('hr.settings.centers.index')"
        :breadcrumbs="[['label' => __('hr_settings.titles.centers'), 'url' => route('hr.settings.centers.index')], ['label' => $center->name]]"
        :title="$center->name"
        :subtitle="$center->secondary_name !== $center->name ? $center->secondary_name : null"
    >
        <x-slot:meta>
            @if ($center->is_active)
                <span class="inline-flex items-center rounded-md border border-status-good/30 bg-status-good-soft px-2 py-0.5 text-xs font-semibold text-status-good">{{ __('common.active') }}</span>
            @else
                <span class="inline-flex items-center rounded-md border border-secondary-light px-2 py-0.5 text-xs font-semibold text-secondary-dark dark:border-line-strong dark:text-ink">{{ __('hr_settings.inactive') }}</span>
            @endif
        </x-slot:meta>
    </x-ui.detail-header>

    <p class="type-meta {{ $muted }}">{{ __('hr_settings.centers.read_only_note') }}</p>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-ui.stat-tile :label="__('hr_settings.cities.employees')" :value="$totals['employees']" tone="good" />
        <x-ui.stat-tile :label="__('hr_settings.centers.assignments')" :value="$totals['assignments']" />
        <x-ui.stat-tile :label="__('hr_settings.cities.positions')" :value="$totals['positions']" />
        <x-ui.stat-tile :label="__('hr_settings.programs.seats')" :value="$totals['seats']" />
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <x-ui.panel :title="__('hr_settings.centers.details')">
            <dl class="divide-y divide-panel-border">
                @foreach ($facts as $label => $value)
                    <div class="flex items-baseline justify-between gap-4 px-4 py-2.5 text-sm">
                        <dt class="shrink-0 {{ $muted }}">{{ $label }}</dt>
                        <dd class="min-w-0 text-end font-medium" dir="auto">{{ $value !== '' ? $value : '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-ui.panel>

        @foreach ([['title' => __('hr_settings.centers.by_contract_type'), 'rows' => $this->byContractType, 'key' => 'type'], ['title' => __('hr_settings.centers.by_program'), 'rows' => $this->byProgram, 'key' => 'program']] as $group)
            <x-ui.panel :title="$group['title']" wire:key="center-group-{{ $group['key'] }}">
                @if ($group['rows'] === [])
                    <x-ui.empty-state icon="users" :heading="__('hr_settings.people.none')" class="min-h-40!" />
                @else
                    <ul class="divide-y divide-panel-border">
                        @foreach ($group['rows'] as $name => $count)
                            <li class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                                <bdi class="min-w-0 truncate font-medium">{{ $name }}</bdi>
                                <span class="shrink-0 text-xs tabular-nums {{ $muted }}">{{ trans_choice('hr_settings.count_employees', $count, ['count' => $count]) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.panel>
        @endforeach
    </div>

    @include('hr::pages.settings._assignments', ['show' => 'program'])
</section>
