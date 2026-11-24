<?php

use App\Support\Modules\Contracts\EmployeeTabSource;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Hr\Livewire\Concerns\Employees\ManagesBadgeForm;
use Modules\Hr\Livewire\Concerns\Employees\ManagesEmployeeProfile;
use Modules\Hr\Livewire\Concerns\Employees\ManagesEmployeeRecords;
use Modules\Hr\Livewire\Concerns\Employees\ReadsEmployeeProfile;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Entitlements\Entitlements;

new #[Layout('layouts::desk'), Title('hr_employees.titles.show')] class extends Component {
    use ManagesBadgeForm;
    use ManagesEmployeeProfile;
    use ManagesEmployeeRecords;
    use ReadsEmployeeProfile;
    use WithFileUploads;

    public const TABS = ['overview', 'records', 'family', 'contracts', 'leave', 'equipment', 'badges', 'card', 'entitlements', 'journeys'];

    #[Url(except: 'overview')]
    public string $tab = 'overview';

    /**
     * With no employee in the URL this is "My profile": the viewer's own record.
     */
    public function mount(?Employee $employee = null): void
    {
        $this->employeeId = (int) ($employee?->exists ? $employee->id : (Auth::user()?->employee_id ?? abort(404)));

        $this->resolveAccess();

        if (! in_array($this->tab, $this->visibleTabs(), true)) {
            $this->tab = 'overview';
        }
    }

    public function updatedTab(): void
    {
        if (! in_array($this->tab, $this->visibleTabs(), true)) {
            $this->tab = 'overview';
        }
    }

    /**
     * HR's own tabs, then the ones other modules add (ModuleRegistry::addEmployeeTabs()).
     *
     * @return list<string>
     */
    public function visibleTabs(): array
    {
        return [...$this->visibleOwnTabs(), ...array_keys($this->moduleTabs())];
    }

    /**
     * The module tabs this login sees on this profile, by key.
     *
     * @return array<string, EmployeeTabSource>
     */
    public function moduleTabs(): array
    {
        $viewer = Auth::user();
        $tabs = [];

        foreach (app(ModuleRegistry::class)->employeeTabSources() as $source) {
            if ($viewer !== null && ! in_array($source->key(), self::TABS, true) && $source->visibleTo($viewer, $this->employeeId)) {
                $tabs[$source->key()] = $source;
            }
        }

        return $tabs;
    }

    /**
     * @return list<string>
     */
    private function visibleOwnTabs(): array
    {
        return array_values(array_filter(self::TABS, fn (string $tab): bool => match ($tab) {
            'records' => $this->canViewRecords,
            'family' => $this->canViewPrivate,
            'contracts' => $this->canViewRecords,
            'leave' => $this->isSelf || $this->canViewRecords,
            'equipment' => $this->canEdit,
            'badges' => $this->canViewBadges,
            'card' => $this->canViewCard,
            'entitlements' => app(Entitlements::class)->canAssign(Auth::user(), $this->employee),
            'journeys' => $this->canEdit,
            default => true,
        }));
    }
};
?>

@use('App\Support\BackUrl')
@use('Modules\Core\Models\City')
@use('Modules\Core\Models\Program')
@use('Modules\Hr\Enums\EmployeeStatus')

@php
    $employee = $this->employee;
    $currentContract = $this->currentContract;
    $position = $currentContract?->position;
    $status = EmployeeStatus::for($employee);
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $ink = 'text-secondary-dark dark:text-ink';
    $fallback = auth()->user()->can('employees.view') ? route('hr.employees.index') : route('dashboard');
    $moduleTabs = $this->moduleTabs();
    $tabs = array_map(fn (string $key): array => ['key' => $key, 'label' => match (true) {
        isset($moduleTabs[$key]) => $moduleTabs[$key]->label(),
        $key === 'journeys' => __('hr_journeys.tab'),
        default => __('hr_employees.tabs.'.$key),
    }], $this->visibleTabs());
@endphp

<section class="@container/directory flex flex-col gap-5" data-employee-profile="{{ $employee->id }}">
    <x-ui.detail-header
        :back="BackUrl::for($fallback)"
        :breadcrumbs="[
            ['label' => __('hr_employees.titles.index'), 'url' => auth()->user()->can('employees.view') ? route('hr.employees.index') : null],
            ['label' => $employee->name],
        ]"
        :title="$employee->name"
        :subtitle="$employee->secondary_name"
    >
        <x-slot:meta>
            <x-hr::employees.status-badge :status="$status" />
            <x-hr::employees.presence :status="$this->presence" with-label />
            @if ($position !== null)
                <span dir="auto">{{ $position->title }}</span>
                @if ($position->program_id)
                    <span>{{ Program::nameOf($position->program_id) }}</span>
                @endif
            @endif
            <span>{{ City::nameOf($employee->city_id) }}</span>
            @if ($employee->is_board_member)
                <span class="rounded-md bg-secondary-soft px-1.5 py-0.5 font-semibold {{ $ink }} dark:bg-surface-2">{{ __('hr_employees.fields.is_board_member') }}</span>
            @endif
        </x-slot:meta>

        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @if (! $this->isSelf)
                    <x-hr::employees.message-template-menu :employee="$employee" />
                @elseif (Route::has('security.edit'))
                    {{-- Their sign-in (password, codes, devices) is the account's own page, behind its password check. --}}
                    <x-ui.button icon="lock-closed" :href="route('security.edit')" wire:navigate data-test="own-security">{{ __('common.security') }}</x-ui.button>
                @endif
                @if ($this->dontRenewUrl !== null)
                    {{-- The direct manager asks the city's HR not to renew: a new email, nothing is sent from here. --}}
                    <x-ui.button icon="envelope" :href="$this->dontRenewUrl" :title="__('hr_journeys.dont_renew.hint')" data-test="dont-renew">{{ __('hr_journeys.dont_renew.action') }}</x-ui.button>
                @endif
                @if ($canEdit)
                    <x-ui.button icon="pencil-square" :href="route('hr.employees.edit', $employee->id)" wire:navigate data-test="edit-employee">{{ __('hr_employees.actions.edit_record') }}</x-ui.button>
                @endif
                @if ($canEdit && ! $employee->isDraft() && $this->contracts->isEmpty())
                    <x-ui.button icon="bookmark" wire:click="openDraft" data-test="reserve-position">{{ __('hr_employees.actions.reserve_position') }}</x-ui.button>
                @endif
                @if (! $employee->isDraft() && auth()->user()->can('contracts.create') && $canEdit)
                    <x-ui.button tone="primary" icon="plus" :href="route('hr.contracts.create', ['employee' => $employee->id])" wire:navigate data-test="add-contract">{{ __('hr_employees.actions.add_contract') }}</x-ui.button>
                @endif
            </div>
        </x-slot:actions>
    </x-ui.detail-header>

    <x-ui.relation-tabs :tabs="$tabs" :active="$tab" />

    @if ($tab === 'overview')
        @include('hr::pages.employees._show-overview')
    @elseif ($tab === 'records')
        @include('hr::pages.employees._show-records')
    @elseif ($tab === 'family')
        @include('hr::pages.employees._show-family')
    @elseif ($tab === 'contracts')
        @include('hr::pages.employees._show-contracts')
    @elseif ($tab === 'leave')
        @include('hr::pages.employees._show-leave')
    @elseif ($tab === 'equipment')
        <livewire:hr::checklists.employee-checklist :employee-id="$employee->id" :key="'employee-equipment-'.$employee->id" />
    @elseif ($tab === 'badges')
        @include('hr::pages.employees._show-badges')
    @elseif ($tab === 'card')
        @include('hr::pages.employees._show-card')
    @elseif ($tab === 'entitlements')
        <livewire:hr::employees.entitlements :employee-id="$employee->id" :key="'employee-entitlements-'.$employee->id" />
    @elseif ($tab === 'journeys')
        <livewire:hr::employees.journeys :employee-id="$employee->id" :key="'employee-journeys-'.$employee->id" lazy />
    @elseif (isset($moduleTabs[$tab]))
        <livewire:dynamic-component :is="$moduleTabs[$tab]->component()" :employee-id="$employee->id" :key="'employee-tab-'.$tab.'-'.$employee->id" />
    @endif

    @include('hr::pages.employees._show-modals')
</section>
