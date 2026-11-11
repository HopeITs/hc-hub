<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Hr\Support\Settings\HrAccess;
use Modules\Hr\Support\Settings\ReferenceTables;

/*
 * HR settings: one page for HR's reference tables (the old "Basic
 * Settings"). It only chooses which tab to mount; every tab is its own
 * component with its own list, dialog, validation and permission checks.
 * The tab lives in the query string (?tab=departments), so it can be
 * bookmarked and works with the back button.
 */
new #[Layout('layouts::desk'), Title('hr_settings.titles.index')] class extends Component {
    #[Url(as: 'tab', history: true, except: 'cities')]
    public string $tab = 'cities';

    public function mount(): void
    {
        Gate::authorize('project-basics.view');

        if (! in_array($this->tab, $this->tabKeys(), true)) {
            $this->tab = 'cities';
        }
    }

    public function updatedTab(): void
    {
        if (! in_array($this->tab, $this->tabKeys(), true)) {
            $this->tab = 'cities';
        }
    }

    /** @return list<string> */
    private function tabKeys(): array
    {
        return ['cities', ...ReferenceTables::keys()];
    }

    /** @return list<array{key: string, label: string}> */
    #[Computed]
    public function tabs(): array
    {
        return array_map(
            fn (string $key): array => ['key' => $key, 'label' => __('hr_settings.tabs.'.$key)],
            $this->tabKeys(),
        );
    }

    /** Holidays have their own page; only national HR sets them from here. */
    #[Computed]
    public function holidaysUrl(): ?string
    {
        return Route::has('hr.leave.holidays') && HrAccess::for(Auth::user())->isNational()
            ? route('hr.leave.holidays')
            : null;
    }
};
?>

<x-desk.register class="@container/directory" heading-id="hr-settings-heading" :title="__('hr_settings.titles.index')" data-hr-settings>
    <x-slot:actions>
        @if ($this->holidaysUrl !== null)
            <x-ui.button tone="action" icon="calendar-days" :href="$this->holidaysUrl" wire:navigate data-test="hr-settings-holidays">
                {{ __('hr_settings.tabs.holidays') }}
            </x-ui.button>
        @endif
        @can('programs.view')
            <x-ui.button tone="action" icon="rectangle-stack" :href="route('hr.settings.programs.index')" wire:navigate>
                {{ __('hr_settings.titles.programs') }}
            </x-ui.button>
        @endcan
        @can('centers.view')
            <x-ui.button tone="action" icon="building-office-2" :href="route('hr.settings.centers.index')" wire:navigate>
                {{ __('hr_settings.titles.centers') }}
            </x-ui.button>
        @endcan
    </x-slot:actions>

    <x-ui.relation-tabs :tabs="$this->tabs" :active="$tab" wire-target="tab" class="mb-4" aria-label="{{ __('hr_settings.titles.index') }}" />

    <div role="tabpanel" data-hr-settings-tab="{{ $tab }}">
        @if ($tab === 'cities')
            <livewire:hr::settings.cities :key="'hr-settings-cities'" />
        @else
            <livewire:hr::settings.reference-table :table="$tab" :key="'hr-settings-'.$tab" />
        @endif
    </div>
</x-desk.register>
