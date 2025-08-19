<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <x-forms.input-label for="start_date">@lang('general.start-date')</x-forms.input-label>
        <x-forms.input id="start_date" type="date" wire:model.defer="form.start_date"/>
        <x-error-span>{{ $errors->first('form.start_date') }}</x-error-span>
    </div>

    <div>
        <x-forms.input-label for="end_date">@lang('general.end-date')</x-forms.input-label>
        <x-forms.input id="end_date" type="date" wire:model.defer="form.end_date"/>
        <x-error-span>{{ $errors->first('form.end_date') }}</x-error-span>
    </div>

    <div>
        <x-forms.input-label for="weekly_hours">@lang('general.weekly-hours')</x-forms.input-label>
        <x-forms.input id="weekly_hours" type="number" min="0" wire:model.defer="form.weekly_hours" placeholder="40"/>
        <x-error-span>{{ $errors->first('form.weekly_hours') }}</x-error-span>
    </div>

    <div>
        <x-forms.input-label for="yearly_leave_days">@lang('general.yearly-leave-days')</x-forms.input-label>
        <x-forms.input id="yearly_leave_days" type="number" min="0" wire:model.defer="form.yearly_leave_days" placeholder="12"/>
        <x-error-span>{{ $errors->first('form.yearly_leave_days') }}</x-error-span>
    </div>

    <div>
        <x-forms.input-label for="terminated_at">@lang('general.terminated-at')</x-forms.input-label>
        <x-forms.input id="terminated_at" type="date" wire:model.defer="form.terminated_at"/>
        <x-error-span>{{ $errors->first('form.terminated_at') }}</x-error-span>
    </div>

    <div class="md:col-span-2">
        <x-forms.input-label for="reason_of_termination">@lang('general.reason-of-termination')</x-forms.input-label>
        <x-forms.textarea id="reason_of_termination" rows="2" wire:model.defer="form.reason_of_termination"
                          placeholder="{{ __('general.optional') }}"/>
        <x-error-span>{{ $errors->first('form.reason_of_termination') }}</x-error-span>
    </div>
</div>
