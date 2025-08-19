@php($locale = app()->getLocale())
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <x-forms.input-label for="employee_id">@lang('general.employee')</x-forms.input-label>
        <x-forms.select-input id="employee_id" wire:model="form.employee_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}">{{ $e->getTranslation('name', $locale) }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span>{{ $errors->first('form.employee_id') }}</x-error-span>
    </div>

    <div>
        <x-forms.input-label for="position_id">{{ trans_choice('general.position',1) }}</x-forms.input-label>
        <x-forms.select-input id="position_id" wire:model="form.position_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($positions as $p)
                <option value="{{ $p->id }}">#{{ $p->id }} — {{ $p->center?->name ?? ('Center #'.$p->center_id) }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span>{{ $errors->first('form.position_id') }}</x-error-span>
    </div>

    <div>
        <x-forms.input-label for="contract_type_id">{{ trans_choice('general.contract-type',1) }}</x-forms.input-label>
        <x-forms.select-input id="contract_type_id" wire:model="form.contract_type_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($types as $t)
                <option value="{{ $t->id }}">{{ $t->getTranslation('name', $locale) }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span>{{ $errors->first('form.contract_type_id') }}</x-error-span>
    </div>

    <div>
        <x-forms.input-label for="department_id">{{ trans_choice('general.department',1) }}</x-forms.input-label>
        <x-forms.select-input id="department_id" wire:model="form.department_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span>{{ $errors->first('form.department_id') }}</x-error-span>
    </div>

    <div class="md:col-span-2">
        <x-forms.input-label for="job_level_id">{{ trans_choice('general.job-level',1) }}</x-forms.input-label>
        <x-forms.select-input id="job_level_id" wire:model="form.job_level_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($levels as $l)
                <option value="{{ $l->id }}">{{ $l->getTranslation('name', $locale) }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span>{{ $errors->first('form.job_level_id') }}</x-error-span>
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" wire:model="form.is_active" class="rounded border-gray-300">
            <span>@lang('general.is-active')</span>
        </label>
    </div>
</div>
