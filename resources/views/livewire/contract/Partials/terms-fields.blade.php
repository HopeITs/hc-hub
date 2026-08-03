@php($locale = app()->getLocale())
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">

    {{-- Start date --}}
    <div>
        <x-forms.input-label for="start_date" required>@lang('general.start-date')</x-forms.input-label>
        <x-forms.date-input id="start_date" wire:model.defer="form.start_date"/>
        <x-error-span>{{ $errors->first('form.start_date') }}</x-error-span>
    </div>

    {{-- End date --}}
    <div>
        <x-forms.input-label for="end_date" required>@lang('general.end-date')</x-forms.input-label>
        <x-forms.date-input id="end_date" wire:model.defer="form.end_date"/>
        <x-error-span>{{ $errors->first('form.end_date') }}</x-error-span>
    </div>

    <div class="grid grid-cols-2 gap-x-4">
        {{-- Start time --}}
        <div>
            <x-forms.input-label for="work_hours_from">@lang('general.work-hours-from')</x-forms.input-label>
            <x-forms.time-picker id="work_hours_from" wire-model="form.work_hours_from" format="HH:mm" />
            <x-error-span>{{ $errors->first('form.work_hours_from') }}</x-error-span>
        </div>

        {{-- End time --}}
        <div>
            <x-forms.input-label for="work_hours_to">@lang('general.work-hours-to')</x-forms.input-label>
            <x-forms.time-picker id="work_hours_to" wire-model="form.work_hours_to" format="HH:mm" />
            <x-error-span>{{ $errors->first('form.work_hours_to') }}</x-error-span>
        </div>
    </div>

    {{-- Weekly hours --}}
    <div>
        <x-forms.input-label for="weekly_hours" required>@lang('general.weekly-hours')</x-forms.input-label>
        <div class="relative mt-1">
            <x-forms.input
                id="weekly_hours"
                type="number" min="0"
                wire:model.defer="form.weekly_hours"
                placeholder="40"
                class="pe-14"
            />
            <span class="absolute inset-y-0 flex items-center text-xs text-gray-500 pointer-events-none end-3">
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

    @foreach(config('app.supported_locales') as $notesLocale)
        <div class="col-span-1">
            <x-forms.input-label for="attending_notes_{{ $notesLocale }}">
                @lang('general.attending-notes') ({{ strtoupper($notesLocale) }})
            </x-forms.input-label>

            <x-forms.textarea
                id="attending_notes_{{ $notesLocale }}"
                wire:model.defer="form.attending_notes_{{ $notesLocale }}"
                maxlength="255"
                rows="3"
                dir="{{ $notesLocale === 'ar' ? 'rtl' : 'ltr' }}"
                placeholder="{{ __('general.attending-notes-placeholder-'.$notesLocale) }}"
            />

            <x-error-span>{{ $errors->first('form.attending_notes_'.$notesLocale) }}</x-error-span>
        </div>
    @endforeach

    <hr class="my-3 col-span-full">

    {{-- Terminated at --}}
    <div class="md:col-span-1">
        <x-forms.input-label for="terminated_at">@lang('general.terminated-at')</x-forms.input-label>
        <x-forms.date-input id="terminated_at" wire:model.defer="form.terminated_at"/>
        <x-error-span>{{ $errors->first('form.terminated_at') }}</x-error-span>
    </div>

     <div class="md:col-span-1">
        <x-forms.input-label for="termination_reason_id">@lang('general.reason-of-termination')</x-forms.input-label>
                <x-forms.select-input wire:model.defer="form.termination_reason_id" label="Reason">
                            <option value=""></option>
                @foreach($terminationReasons as $reason)
                    <option value="{{ $reason->id }}">{{ $reason->title }}</option>
                @endforeach
            </x-forms.select-input>
        <x-error-span>{{ $errors->first('form.termination_reason_id') }}</x-error-span>
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
