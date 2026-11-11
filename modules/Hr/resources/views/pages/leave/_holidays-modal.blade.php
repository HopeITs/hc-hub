{{--
    The add/edit holiday dialog (ManagesHolidayForm). Included by ⚡holidays
    and ⚡team-calendar. National HR picks the scope and may set the birthday
    window; a program manager only adds official holidays and grants to the
    projects they are responsible for.
--}}
@php
    $holidayKind = $holidayForm['kind'] ?? 'official';
    $isNational = $this->isNationalHr;
    $kindOptions = [
        ['value' => 'official', 'label' => __('hr_leave_calendar.holidays.kinds.official')],
        ['value' => 'grant', 'label' => __('hr_leave_calendar.holidays.kinds.grant')],
    ];

    if ($isNational) {
        $kindOptions[] = ['value' => 'birthday', 'label' => __('hr_leave_calendar.holidays.kinds.birthday')];
    }
@endphp

<x-ui.modal
    wire:model="showHolidayModal"
    size="md"
    :title="$editingHolidayId === null ? __('hr_leave_calendar.holidays.add') : __('hr_leave_calendar.holidays.edit')"
    :description="__('hr_leave_calendar.holidays.dialog_description')"
    submit="saveHoliday"
    close="closeHolidayModal"
    required-note
>
    @if ($showHolidayModal)
        <div class="grid gap-y-3" data-holiday-form>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-ui.form-input wire:model="holidayForm.name_en" :label="__('hr_leave_calendar.holidays.name_en')" required maxlength="255" dir="ltr" lang="en" />
                <x-ui.form-input wire:model="holidayForm.name_ar" :label="__('hr_leave_calendar.holidays.name_ar')" required maxlength="255" dir="rtl" lang="ar" />
            </div>

            <x-ui.form-segmented
                wire:model.live="holidayForm.kind"
                :label="__('hr_leave_calendar.holidays.kind')"
                :options="$kindOptions"
                :help="__('hr_leave_calendar.holidays.kind_hints.'.$holidayKind)"
                required
            />

            @if ($isNational)
                <x-ui.form-segmented
                    wire:model.live="holidayForm.scope"
                    :label="__('hr_leave_calendar.holidays.applies_to')"
                    :options="[
                        ['value' => 'national', 'label' => __('hr_leave_calendar.holidays.national')],
                        ['value' => 'projects', 'label' => __('hr_leave_calendar.holidays.selected_projects')],
                    ]"
                    required
                />
            @else
                @error('holidayForm.scope')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            @endif

            @if (($holidayForm['scope'] ?? null) === 'projects')
                @php
                    $projectError = $errors->first('holidayForm.projects') ?: $errors->first('holidayForm.projects.*');
                @endphp
                <div class="min-w-0" data-holiday-projects>
                    <p class="form-label">{{ __('hr_leave_calendar.holidays.projects') }} <span class="form-required" aria-hidden="true">*</span></p>

                    @if (count($this->projectGovernorates) > 1)
                        <div class="mb-2 flex flex-wrap items-end gap-2">
                            <x-ui.form-select wire:model="holidayGovernorate" :label="__('hr_leave_calendar.holidays.governorate')" class="min-w-48 flex-1">
                                <option value="">{{ __('hr_leave_calendar.holidays.governorate') }}</option>
                                @foreach ($this->projectGovernorates as $cityId => $cityName)
                                    <option value="{{ $cityId }}">{{ $cityName }}</option>
                                @endforeach
                            </x-ui.form-select>
                            <x-ui.button type="button" tone="secondary" wire:click="selectGovernorateProjects">{{ __('hr_leave_calendar.holidays.select_governorate') }}</x-ui.button>
                        </div>
                    @endif

                    <div class="max-h-48 space-y-1 overflow-y-auto rounded-lg border border-secondary-soft p-2 dark:border-line">
                        @forelse ($this->projectOptions as $projectKey => $projectLabel)
                            <label class="flex items-center gap-2 text-sm" wire:key="holiday-project-{{ $projectKey }}">
                                <input type="checkbox" wire:model="holidayForm.projects" value="{{ $projectKey }}" class="rounded border-secondary-light text-primary dark:border-line-strong">
                                <bdi>{{ $projectLabel }}</bdi>
                            </label>
                        @empty
                            <p class="text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_leave_calendar.holidays.no_projects_to_pick') }}</p>
                        @endforelse
                    </div>

                    @if ($projectError !== '')
                        <p class="form-error"><flux:icon.exclamation-circle variant="micro" class="size-3.5 shrink-0" />{{ $projectError }}</p>
                    @endif
                </div>
            @endif

            <div class="grid gap-3 sm:grid-cols-2">
                <x-ui.form-input wire:model="holidayForm.start_date" type="date" :label="__($holidayKind === 'birthday' ? 'hr_leave_calendar.holidays.valid_from' : 'hr_leave_calendar.holidays.from')" required />
                <x-ui.form-input wire:model="holidayForm.end_date" type="date" :label="__($holidayKind === 'birthday' ? 'hr_leave_calendar.holidays.valid_to' : 'hr_leave_calendar.holidays.to')" required />
            </div>

            @if ($holidayKind !== 'grant')
                <flux:checkbox wire:model="holidayForm.is_recurring" :label="__('hr_leave_calendar.holidays.recurring')" :description="__('hr_leave_calendar.holidays.recurring_hint')" />
            @endif
        </div>
    @endif

    <x-slot:actions>
        <x-ui.button type="submit" tone="primary" data-test="save-holiday">{{ __('common.save_changes') }}</x-ui.button>
    </x-slot:actions>
</x-ui.modal>
