{{--
    The add/edit holiday dialog (ManagesHolidayForm). Included by ⚡holidays
    and ⚡team-calendar. National HR picks the scope and may set the birthday
    window; a program manager only adds official holidays and grants to the
    projects they are responsible for.
--}}
@php
    $holidayKind = $holidayForm['kind'] ?? 'official';
    $isNational = $this->isNationalHr;
    $kindIcons = ['official' => 'calendar-days', 'grant' => 'gift'] + ($isNational ? ['birthday' => 'cake'] : []);
    $scopeChoices = [
        'national' => ['globe-alt', 'hr_leave.calendar.holidays.national'],
        'projects' => ['folder', 'hr_leave.calendar.holidays.selected_projects'],
    ];
    $choiceCard = 'rounded-md border border-panel-border transition peer-checked:border-primary peer-checked:bg-secondary-soft/60 peer-focus-visible:ring-2 peer-focus-visible:ring-primary dark:peer-checked:border-brand dark:peer-checked:bg-surface-2';
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<x-ui.modal
    wire:model="showHolidayModal"
    size="md"
    :title="$editingHolidayId === null ? __('hr_leave.calendar.holidays.add') : __('hr_leave.calendar.holidays.edit')"
    :description="__('hr_leave.calendar.holidays.dialog_description')"
    submit="saveHoliday"
    close="closeHolidayModal"
    required-note
>
    @if ($showHolidayModal)
        <div class="grid gap-y-3" data-holiday-form>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-ui.form-input wire:model="holidayForm.name_en" :label="__('hr_leave.calendar.holidays.name_en')" required maxlength="255" dir="ltr" lang="en" />
                <x-ui.form-input wire:model="holidayForm.name_ar" :label="__('hr_leave.calendar.holidays.name_ar')" required maxlength="255" dir="rtl" lang="ar" />
            </div>

            <fieldset class="min-w-0" data-holiday-kinds>
                <legend class="form-label">{{ __('hr_leave.calendar.holidays.kind') }} <span class="form-required" aria-hidden="true">*</span></legend>
                <div @class(['grid gap-2', 'sm:grid-cols-3' => count($kindIcons) === 3, 'sm:grid-cols-2' => count($kindIcons) === 2])>
                    @foreach ($kindIcons as $kind => $icon)
                        <label class="min-w-0 cursor-pointer" wire:key="holiday-kind-{{ $kind }}">
                            <input type="radio" class="peer sr-only" name="holiday_kind" value="{{ $kind }}" wire:model.live="holidayForm.kind" @checked($holidayKind === $kind)>
                            <span class="flex h-full items-start gap-2.5 p-3 {{ $choiceCard }}">
                                <flux:icon :icon="$icon" variant="mini" class="mt-0.5 size-5 shrink-0 {{ $muted }}" />
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold">{{ __('hr_leave.calendar.holidays.kinds.'.$kind) }}</span>
                                    <span class="mt-0.5 block text-xs {{ $muted }}">{{ __('hr_leave.calendar.holidays.kind_hints.'.$kind) }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('holidayForm.kind')
                    <p class="form-error"><flux:icon.exclamation-circle variant="micro" class="size-3.5 shrink-0" />{{ $message }}</p>
                @enderror
            </fieldset>

            @if ($isNational)
                <fieldset class="min-w-0" data-holiday-scopes>
                    <legend class="form-label">{{ __('hr_leave.calendar.holidays.applies_to') }} <span class="form-required" aria-hidden="true">*</span></legend>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($scopeChoices as $scope => [$icon, $label])
                            <label class="min-w-0 cursor-pointer" wire:key="holiday-scope-{{ $scope }}">
                                <input type="radio" class="peer sr-only" name="holiday_scope" value="{{ $scope }}" wire:model.live="holidayForm.scope" @checked(($holidayForm['scope'] ?? null) === $scope)>
                                <span class="flex min-h-11 items-center justify-center gap-2 px-3 py-2 {{ $choiceCard }}">
                                    <flux:icon :icon="$icon" variant="mini" class="size-4 shrink-0 {{ $muted }}" />
                                    <span class="text-sm font-semibold">{{ __($label) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('holidayForm.scope')
                        <p class="form-error"><flux:icon.exclamation-circle variant="micro" class="size-3.5 shrink-0" />{{ $message }}</p>
                    @enderror
                </fieldset>
            @else
                @error('holidayForm.scope')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            @endif

            @if (($holidayForm['scope'] ?? null) === 'projects')
                @php
                    $projectError = $errors->first('holidayForm.projects') ?: $errors->first('holidayForm.projects.*');
                    $tickedProjects = $holidayForm['projects'] ?? [];
                @endphp
                <div class="min-w-0" data-holiday-projects>
                    <p class="form-label">{{ __('hr_leave.calendar.holidays.projects') }} <span class="form-required" aria-hidden="true">*</span></p>

                    {{-- One block per governorate: its header ticks or clears every project there and leaves the other governorates alone. --}}
                    <div class="max-h-64 overflow-y-auto rounded-lg border border-secondary-soft dark:border-line">
                        @forelse ($this->projectGroups as $group)
                            @php
                                $groupKeys = array_column($group['options'], 'value');
                                $tickedHere = count(array_intersect($groupKeys, $tickedProjects));
                            @endphp
                            <div wire:key="holiday-city-{{ $group['id'] }}" data-holiday-city="{{ $group['id'] }}">
                                <div class="sticky top-0 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-b border-secondary-soft bg-secondary-soft px-3 py-1.5 dark:border-line dark:bg-surface-2">
                                    <p class="text-sm font-semibold">
                                        <bdi>{{ $group['name'] }}</bdi>
                                        <span class="ms-1 text-xs font-normal {{ $muted }}">{{ __('hr_leave.calendar.holidays.selected_of', ['selected' => $tickedHere, 'total' => count($groupKeys)]) }}</span>
                                    </p>
                                    <span class="flex items-center gap-1">
                                        <x-ui.button type="button" tone="ghost" size="xs" wire:click="selectCityProjects({{ $group['id'] }})" :disabled="$tickedHere === count($groupKeys)" :aria-label="__('hr_leave.calendar.holidays.select_all_in', ['city' => $group['name']])" data-test="holiday-select-city">{{ __('hr_leave.calendar.holidays.select_all') }}</x-ui.button>
                                        <x-ui.button type="button" tone="ghost" size="xs" wire:click="clearCityProjects({{ $group['id'] }})" :disabled="$tickedHere === 0" :aria-label="__('hr_leave.calendar.holidays.clear_all_in', ['city' => $group['name']])" data-test="holiday-clear-city">{{ __('hr_leave.calendar.holidays.clear_all') }}</x-ui.button>
                                    </span>
                                </div>
                                <div class="space-y-1 p-2">
                                    @foreach ($group['options'] as $option)
                                        <label class="flex cursor-pointer items-center gap-2 text-sm" wire:key="holiday-project-{{ $option['value'] }}">
                                            <input type="checkbox" wire:model.live="holidayForm.projects" value="{{ $option['value'] }}" class="rounded border-secondary-light text-primary dark:border-line-strong">
                                            <bdi>{{ $option['label'] }}</bdi>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="p-2 text-sm {{ $muted }}">{{ __('hr_leave.calendar.holidays.no_projects_to_pick') }}</p>
                        @endforelse
                    </div>

                    @if ($projectError !== '')
                        <p class="form-error"><flux:icon.exclamation-circle variant="micro" class="size-3.5 shrink-0" />{{ $projectError }}</p>
                    @endif
                </div>
            @endif

            <div class="grid gap-3 sm:grid-cols-2">
                <x-ui.form-input wire:model="holidayForm.start_date" type="date" :label="__($holidayKind === 'birthday' ? 'hr_leave.calendar.holidays.valid_from' : 'hr_leave.calendar.holidays.from')" required />
                <x-ui.form-input wire:model="holidayForm.end_date" type="date" :label="__($holidayKind === 'birthday' ? 'hr_leave.calendar.holidays.valid_to' : 'hr_leave.calendar.holidays.to')" required />
            </div>

            @if ($holidayKind !== 'grant')
                <flux:checkbox wire:model="holidayForm.is_recurring" :label="__('hr_leave.calendar.holidays.recurring')" :description="__('hr_leave.calendar.holidays.recurring_hint')" />
            @endif
        </div>
    @endif

    <x-slot:actions>
        <x-ui.button type="submit" tone="primary" data-test="save-holiday">{{ __('common.save_changes') }}</x-ui.button>
    </x-slot:actions>
</x-ui.modal>
