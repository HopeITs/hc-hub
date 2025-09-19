@php($locale = app()->getLocale())
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    {{-- Start date --}}
    <div>
        <x-forms.input-label for="start_date">@lang('general.start-date')</x-forms.input-label>
        <x-forms.date-input id="start_date" wire:model.defer="form.start_date" class="mt-1" />
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.start_date') }}</x-error-span>
    </div>

    {{-- End date --}}
    <div>
        <x-forms.input-label for="end_date">@lang('general.end-date')</x-forms.input-label>
        <x-forms.date-input id="end_date" wire:model.defer="form.end_date" class="mt-1" />
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.end_date') }}</x-error-span>
    </div>

    {{-- Weekly hours --}}
    <div>
        <x-forms.input-label for="weekly_hours">@lang('general.weekly-hours')</x-forms.input-label>
        <div class="relative mt-1">
            <x-forms.input
                id="weekly_hours"
                type="number" min="0"
                wire:model.defer="form.weekly_hours"
                placeholder="40"
                class="pr-14"
            />
            <span class="absolute inset-y-0 end-3 flex items-center text-xs text-gray-500 pointer-events-none">
                {{ $locale === 'ar' ? 'س/أسبوع' : 'h/week' }}
            </span>
        </div>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.weekly_hours') }}</x-error-span>
    </div>

    {{-- Yearly leave days --}}
    <div>
        <x-forms.input-label for="yearly_leave_days">@lang('general.yearly-leave-days')</x-forms.input-label>
        <div class="relative mt-1">
            <x-forms.input
                id="yearly_leave_days"
                type="number" min="0"
                wire:model.defer="form.yearly_leave_days"
                placeholder="12"
                class="pr-14"
            />
            <span class="absolute inset-y-0 end-3 flex items-center text-xs text-gray-500 pointer-events-none">
                {{ $locale === 'ar' ? 'يوم/سنة' : 'days/yr' }}
            </span>
        </div>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.yearly_leave_days') }}</x-error-span>
    </div>

    {{-- Terminated at --}}
    <div>
        <x-forms.input-label for="terminated_at">@lang('general.terminated-at')</x-forms.input-label>
        <x-forms.date-input id="terminated_at" wire:model.defer="form.terminated_at" class="mt-1" />
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.terminated_at') }}</x-error-span>
    </div>

    {{-- Reason of termination --}}
    <div class="md:col-span-2">
        <x-forms.input-label for="reason_of_termination">@lang('general.reason-of-termination')</x-forms.input-label>
        <x-forms.textarea
            id="reason_of_termination"
            rows="3"
            wire:model.defer="form.reason_of_termination"
            class="mt-1"
            placeholder="{{ __('general.optional') }}"
        />
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.reason_of_termination') }}</x-error-span>
    </div>

</div>
