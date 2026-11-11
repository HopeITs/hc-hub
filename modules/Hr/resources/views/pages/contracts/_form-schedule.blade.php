{{--
    The contract form's schedule panel: a fixed timetable (day range, one or
    two shifts) or a flexible one. Included by ⚡form.blade.php.
--}}
@use('Modules\Hr\Enums\ScheduleMode')
@use('Modules\Hr\Support\Contracts\ContractOptions')

@php
    $fixedSchedule = (int) ($form['schedule_mode'] ?? 0) === ScheduleMode::Fixed->value;
@endphp

<x-ui.panel :title="__('hr_contracts.form.schedule')">
    <div class="p-4 {{ $grid }}">
        <x-ui.form-select wire:model.live="form.schedule_mode" :label="__('hr_contracts.fields.schedule_mode')" required>
            <option value="{{ ScheduleMode::Fixed->value }}">{{ __('hr_contracts.schedule.fixed') }}</option>
            <option value="{{ ScheduleMode::Flexible->value }}">{{ __('hr_contracts.schedule.flexible') }}</option>
        </x-ui.form-select>
        <x-ui.form-input wire:model="form.weekly_hours" :label="__('hr_contracts.fields.weekly_hours')" type="number" min="0" max="168" step="1" required />
        <span class="hidden @5xl/form:block"></span>

        @if ($fixedSchedule)
            <x-ui.form-select wire:model="form.work_day_from" :label="__('hr_contracts.fields.work_day_from')" :placeholder="__('common.select')">
                @foreach (ContractOptions::WEEK_DAYS as $dayIndex => $dayKey)
                    <option value="{{ $dayIndex }}">{{ __('hr_contracts.days.'.$dayKey) }}</option>
                @endforeach
            </x-ui.form-select>
            <x-ui.form-select wire:model="form.work_day_to" :label="__('hr_contracts.fields.work_day_to')" :placeholder="__('common.select')">
                @foreach (ContractOptions::WEEK_DAYS as $dayIndex => $dayKey)
                    <option value="{{ $dayIndex }}">{{ __('hr_contracts.days.'.$dayKey) }}</option>
                @endforeach
            </x-ui.form-select>
            <span class="hidden @5xl/form:block"></span>
            <x-ui.form-input wire:model="form.work_hours_from" :label="__('hr_contracts.fields.work_hours_from')" type="time" required />
            <x-ui.form-input wire:model="form.work_hours_to" :label="__('hr_contracts.fields.work_hours_to')" type="time" required />
            <span class="hidden @5xl/form:block"></span>
            <x-ui.form-input wire:model="form.work_hours_2_from" :label="__('hr_contracts.fields.work_hours_2_from')" type="time" />
            <x-ui.form-input wire:model="form.work_hours_2_to" :label="__('hr_contracts.fields.work_hours_2_to')" type="time" />
            <span class="hidden @5xl/form:block"></span>
            <div class="flex flex-col gap-2 @2xl/form:col-span-2 @5xl/form:col-span-3">
                <p class="text-sm font-semibold">{{ __('hr_contracts.form.show_in_clause') }}</p>
                <div class="flex flex-wrap gap-4">
                    <flux:checkbox wire:model="form.show_hours_per_week" :label="__('hr_contracts.fields.show_hours_per_week')" />
                    <flux:checkbox wire:model="form.show_hours_per_day" :label="__('hr_contracts.fields.show_hours_per_day')" />
                    <flux:checkbox wire:model="form.show_days_per_week" :label="__('hr_contracts.fields.show_days_per_week')" />
                    <flux:checkbox wire:model="form.show_day_range" :label="__('hr_contracts.fields.show_day_range')" />
                </div>
            </div>
        @else
            <x-ui.form-input wire:model="form.flex_days_per_week" :label="__('hr_contracts.fields.flex_days_per_week')" type="number" min="1" max="7" step="1" />
            <x-ui.form-input wire:model="form.flex_hours_per_week" :label="__('hr_contracts.fields.flex_hours_per_week')" type="number" min="1" max="168" step="1" />
            <x-ui.form-input wire:model="form.flex_hours_per_month" :label="__('hr_contracts.fields.flex_hours_per_month')" type="number" min="1" max="300" step="1" />
            <x-ui.form-input wire:model="form.flex_custom_en" :label="__('hr_contracts.fields.flex_custom_en')" maxlength="255" dir="ltr" />
            <x-ui.form-input wire:model="form.flex_custom_ar" :label="__('hr_contracts.fields.flex_custom_ar')" maxlength="255" dir="rtl" />
            <div class="flex flex-col justify-end gap-2">
                <flux:checkbox wire:model="form.flex_varies_by_requirements" :label="__('hr_contracts.fields.flex_varies_by_requirements')" />
                <flux:checkbox wire:model="form.flex_coordinated_with_manager" :label="__('hr_contracts.fields.flex_coordinated_with_manager')" />
            </div>
        @endif
    </div>
</x-ui.panel>
