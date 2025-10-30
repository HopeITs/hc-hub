@php($locale = app()->getLocale())
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Start date --}}
    <div>
        <x-forms.input-label for="start_date">@lang('general.start-date')</x-forms.input-label>
        <x-forms.date-input id="start_date" wire:model.defer="form.start_date"/>
        <x-error-span>{{ $errors->first('form.start_date') }}</x-error-span>
    </div>

    {{-- End date --}}
    <div>
        <x-forms.input-label for="end_date">@lang('general.end-date')</x-forms.input-label>
        <x-forms.date-input id="end_date" wire:model.defer="form.end_date"/>
        <x-error-span>{{ $errors->first('form.end_date') }}</x-error-span>
    </div>

    <div class="grid grid-cols-2 gap-x-4">
        {{-- Start time --}}
        <div>
            <x-forms.input-label for="work_hours_from">@lang('general.work-hours-from')</x-forms.input-label>
            <x-forms.input type="time" id="work_hours_from"  wire:model.defer="form.work_hours_from"/>
            <x-error-span>{{ $errors->first('form.work_hours_from') }}</x-error-span>
        </div>

        {{-- End time --}}
        <div>
            <x-forms.input-label for="work_hours_to">@lang('general.work-hours-to')</x-forms.input-label>
            <x-forms.input type="time" id="work_hours_to"   wire:model.defer="form.work_hours_to"/>
            <x-error-span>{{ $errors->first('form.work_hours_to') }}</x-error-span>
        </div>
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
        <x-error-span>{{ $errors->first('form.weekly_hours') }}</x-error-span>
    </div>


    @foreach(config('app.supported_locales') as $locale)
        <div class="col-span-1">
            <x-forms.input-label for="work_days_{{ $locale }}">@lang('general.work-days')({{ strtoupper($locale) }})
            </x-forms.input-label>
            <x-forms.input type="text" id="work_days_{{ $locale }}"
                           wire:model="form.work_days_{{ $locale }}"/>
            <x-error-span>{{$errors->first('form.work_days_'.$locale)}}</x-error-span>
        </div>
    @endforeach

    <hr class="col-span-full my-3">

    {{-- Terminated at --}}
    <div class="md:col-span-2">
        <x-forms.input-label for="terminated_at">@lang('general.terminated-at')</x-forms.input-label>
        <x-forms.date-input id="terminated_at" wire:model.defer="form.terminated_at"/>
        <x-error-span>{{ $errors->first('form.terminated_at') }}</x-error-span>
    </div>

    {{-- Reason of termination --}}
    <div class="md:col-span-2">
        <x-forms.input-label for="reason_of_termination">@lang('general.reason-of-termination')</x-forms.input-label>
        <x-forms.textarea
            id="reason_of_termination"
            rows="3"
            wire:model.defer="form.reason_of_termination"

            placeholder="{{ __('general.optional') }}"
        />
        <x-error-span>{{ $errors->first('form.reason_of_termination') }}</x-error-span>
    </div>

</div>
