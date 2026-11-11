<?php

use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Core\Models\City;
use Modules\Hr\Actions\Settings\SaveCity;
use Modules\Hr\Actions\Settings\ToggleCityStatus;
use Modules\Hr\Support\Settings\HrAccess;

/*
 * The cities tab of HR settings. Governorates are Core rows that no other
 * page manages: national HR adds one and edits its names, 3-letter slug,
 * ID-card code and HR hotline, and turns it on or off; a regional HR role
 * sees only its own governorates and edits their names and hotline.
 */
new class extends Component {
    public string $search = '';

    public bool $showFormModal = false;

    #[Locked]
    public ?int $editingId = null;

    public string $nameEn = '';

    public string $nameAr = '';

    public string $slug = '';

    public string $code = '';

    public string $hotline = '';

    public bool $showToggleModal = false;

    #[Locked]
    public ?int $togglingId = null;

    public string $togglingName = '';

    public bool $togglingActive = false;

    public function mount(): void
    {
        Gate::authorize('project-basics.view');
    }

    #[Computed]
    public function isNational(): bool
    {
        return HrAccess::for(Auth::user())->isNational();
    }

    /** @return Collection<int, City> */
    #[Computed]
    public function cities(): Collection
    {
        return HrAccess::for(Auth::user())
            ->scopeToCities(City::query(), 'id')
            ->select(['id', 'name_en', 'name_ar', 'slug', 'code', 'hr_hotline', 'is_active'])
            ->search($this->search)
            ->withCount(['centers', 'positions', 'employees'])
            ->orderBy('code')
            ->orderBy('id')
            ->get();
    }

    public function openCreateModal(): void
    {
        Gate::authorize('project-basics.create');
        abort_unless($this->isNational, 403);

        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $cityId): void
    {
        Gate::authorize('project-basics.update');

        $city = HrAccess::for(Auth::user())->scopeToCities(City::query(), 'id')->findOrFail($cityId);

        $this->resetForm();
        $this->editingId = $city->id;
        $this->nameEn = trim($city->name_en);
        $this->nameAr = trim($city->name_ar);
        $this->slug = trim($city->slug);
        $this->code = trim($city->code);
        $this->hotline = (string) $city->hr_hotline;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        try {
            app(SaveCity::class)->handle($this->editingId, [
                'name_en' => $this->nameEn,
                'name_ar' => $this->nameAr,
                'slug' => $this->slug,
                'code' => $this->code,
                'hr_hotline' => $this->hotline,
            ]);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $key): array => [match ($key) {
                    'name_ar' => 'nameAr',
                    'slug' => 'slug',
                    'code' => 'code',
                    'hr_hotline' => 'hotline',
                    default => 'nameEn',
                } => $messages])->all(),
            );
        }

        $created = $this->editingId === null;

        $this->closeFormModal();
        unset($this->cities);

        Flux::toast(variant: 'success', text: __($created ? 'hr_settings.created' : 'hr_settings.updated'));
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function confirmToggle(int $cityId): void
    {
        Gate::authorize('project-basics.update');

        $city = HrAccess::for(Auth::user())->scopeToCities(City::query(), 'id')->findOrFail($cityId);

        $this->togglingId = $city->id;
        $this->togglingName = $city->name;
        $this->togglingActive = $city->is_active;
        $this->showToggleModal = true;
    }

    public function toggle(): void
    {
        if ($this->togglingId === null) {
            return;
        }

        $cityId = $this->togglingId;
        $this->cancelToggle();

        try {
            $city = app(ToggleCityStatus::class)->handle($cityId);
        } catch (ValidationException $exception) {
            Flux::toast(variant: 'danger', text: (string) collect($exception->errors())->flatten()->first());

            return;
        }

        unset($this->cities);

        Flux::toast(variant: 'success', text: __($city->is_active ? 'hr_settings.cities.activated' : 'hr_settings.cities.deactivated'));
    }

    public function cancelToggle(): void
    {
        $this->showToggleModal = false;
        $this->togglingId = null;
        $this->togglingName = '';
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->nameEn = '';
        $this->nameAr = '';
        $this->slug = '';
        $this->code = '';
        $this->hotline = '';
    }
};
?>

@php
    $cities = $this->cities;
    $user = auth()->user();
    $canCreate = $this->isNational && $user->can('project-basics.create');
    $canUpdate = $user->can('project-basics.update');
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $badge = 'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-xs font-semibold whitespace-nowrap';
@endphp

<div data-hr-settings-cities>
    <div class="mb-3 flex flex-wrap items-center gap-2">
        <x-desk.register-search class="min-w-0 flex-1" :label="__('hr_settings.search_in', ['table' => __('hr_settings.tabs.cities')])" wire:model.live.debounce.300ms="search" />

        <span class="text-xs font-semibold tabular-nums {{ $muted }}" data-count-badge>{{ trans_choice('hr_settings.count_rows', $cities->count(), ['count' => $cities->count()]) }}</span>

        @if ($canCreate)
            <x-ui.button tone="primary" icon="plus" class="ms-auto" wire:click="openCreateModal" data-test="hr-settings-add-city" data-quick-create>
                {{ __('hr_settings.add') }}
            </x-ui.button>
        @endif
    </div>

    @if ($cities->isEmpty())
        <x-ui.empty-state icon="map-pin" :heading="__('hr_settings.no_matches')" :body="__('common.change_the_search_and_try_again')" class="rounded-panel border border-panel-border" />
    @else
        <x-ui.data-table :caption="__('hr_settings.tabs.cities')">
            <x-slot:head>
                <th>{{ __('hr_settings.fields.name_ar') }}</th>
                <th>{{ __('hr_settings.fields.name_en') }}</th>
                <th class="w-20!">{{ __('hr_settings.fields.slug') }}</th>
                <th class="w-20!">{{ __('hr_settings.fields.code') }}</th>
                <th class="w-40!">{{ __('hr_settings.fields.hr_hotline') }}</th>
                <th class="w-24!">{{ __('hr_settings.cities.centers') }}</th>
                <th class="w-24!">{{ __('hr_settings.cities.positions') }}</th>
                <th class="w-24!">{{ __('hr_settings.cities.employees') }}</th>
                <th class="w-28!">{{ __('common.status') }}</th>
                @if ($canUpdate)
                    <th class="w-24!"><span class="sr-only">{{ __('common.actions') }}</span></th>
                @endif
            </x-slot:head>

            @foreach ($cities as $city)
                <tr wire:key="city-{{ $city->id }}" data-city-row="{{ $city->id }}" @class(['opacity-70' => ! $city->is_active])>
                    <x-ui.data-table.cell :label="__('hr_settings.fields.name_ar')"><bdi class="font-semibold">{{ trim($city->name_ar) }}</bdi></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.fields.name_en')"><bdi dir="ltr">{{ trim($city->name_en) }}</bdi></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.fields.slug')"><bdi dir="ltr" class="font-mono text-xs">{{ trim($city->slug) }}</bdi></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.fields.code')"><bdi dir="ltr" class="font-mono text-xs">{{ trim($city->code) }}</bdi></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.fields.hr_hotline')">
                        @if (filled($city->hr_hotline))
                            <bdi dir="ltr" class="tabular-nums">{{ $city->hr_hotline }}</bdi>
                        @else
                            <span class="{{ $muted }}">—</span>
                        @endif
                    </x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.cities.centers')" numeric>{{ $city->centers_count }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.cities.positions')" numeric>{{ $city->positions_count }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_settings.cities.employees')" numeric>{{ $city->employees_count }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('common.status')">
                        @if ($city->is_active)
                            <span class="{{ $badge }} border-status-good/30 bg-status-good-soft text-status-good">{{ __('common.active') }}</span>
                        @else
                            <span class="{{ $badge }} border-secondary-light text-secondary-dark dark:border-line-strong dark:text-ink">{{ __('hr_settings.inactive') }}</span>
                        @endif
                    </x-ui.data-table.cell>
                    @if ($canUpdate)
                        <td class="px-3.5 py-2.5">
                            <span class="flex items-center justify-end gap-1">
                                <x-ui.button tone="ghost" size="sm" icon="pencil-square" class="size-8!" wire:click="openEditModal({{ $city->id }})" :aria-label="__('common.edit_name', ['name' => $city->name])" data-test="hr-settings-edit-city" />
                                <x-ui.button tone="ghost" size="sm" :icon="$city->is_active ? 'pause-circle' : 'play-circle'" class="size-8!" wire:click="confirmToggle({{ $city->id }})" :aria-label="__($city->is_active ? 'hr_settings.cities.deactivate_name' : 'hr_settings.cities.activate_name', ['name' => $city->name])" data-test="hr-settings-toggle-city" />
                            </span>
                        </td>
                    @endif
                </tr>
            @endforeach
        </x-ui.data-table>
    @endif

    <x-ui.modal
        wire:model="showFormModal"
        size="sm"
        :title="$editingId === null ? __('hr_settings.cities.add') : __('hr_settings.cities.edit')"
        :description="__('hr_settings.cities.dialog_description')"
        submit="save"
        close="closeFormModal"
        required-note
    >
        @if ($showFormModal)
            <div class="grid gap-y-3">
                <x-ui.form-input wire:model="nameAr" :label="__('hr_settings.fields.name_ar')" required maxlength="255" dir="rtl" lang="ar" />
                <x-ui.form-input wire:model="nameEn" :label="__('hr_settings.fields.name_en')" required maxlength="255" dir="ltr" lang="en" />
                @if ($this->isNational)
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.form-input wire:model="slug" :label="__('hr_settings.fields.slug')" :help="__('hr_settings.cities.slug_help')" required maxlength="3" dir="ltr" class="uppercase" />
                        <x-ui.form-input wire:model="code" :label="__('hr_settings.fields.code')" :help="__('hr_settings.cities.code_help')" required maxlength="3" inputmode="numeric" dir="ltr" />
                    </div>
                @endif
                <x-ui.form-input wire:model="hotline" type="tel" :label="__('hr_settings.fields.hr_hotline')" :help="__('hr_settings.cities.hotline_help')" maxlength="32" dir="ltr" />
            </div>
        @endif

        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="hr-settings-save-city">{{ __('common.save_changes') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>

    <x-ui.confirm-modal
        wire:model="showToggleModal"
        close="cancelToggle"
        tone="neutral"
        :title="__($togglingActive ? 'hr_settings.cities.deactivate_title' : 'hr_settings.cities.activate_title', ['name' => $togglingName])"
        :message="__($togglingActive ? 'hr_settings.cities.deactivate_message' : 'hr_settings.cities.activate_message')"
        confirm="toggle"
        :confirm-label="__($togglingActive ? 'hr_settings.cities.deactivate' : 'hr_settings.cities.activate')"
        confirm-test="hr-settings-confirm-toggle-city"
    />
</div>
