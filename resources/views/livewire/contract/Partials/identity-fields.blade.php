@php($locale = app()->getLocale())

<div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6">

    {{-- Employee --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="employee_id">@lang('general.employee')</x-forms.input-label>
        <x-forms.select-input id="employee_id" wire:model="form.employee_id" class="mt-1">
            <option value="">{{ __('general.select') }}</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}">{{ $e->getTranslation('name', $locale) }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.employee_id') }}</x-error-span>
    </div>

    {{-- Project --}}
    @isset($projects)
        <div class="md:col-span-6">
            <x-forms.input-label for="project_id">@lang('general.project')</x-forms.input-label>
            <x-forms.select-input id="project_id" wire:model="form.project_id" class="mt-1">
                <option value="">{{ __('general.select') }}</option>
                @foreach($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </x-forms.select-input>
            <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.project_id') }}</x-error-span>
        </div>
    @endisset

    {{-- Position --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="position_id">{{ trans_choice('general.positions', 1) }}</x-forms.input-label>
        <x-forms.select-input id="position_id" wire:model="form.position_id" class="mt-1">
            <option value="">{{ __('general.select') }}</option>
            @foreach($positions as $pos)
                <option value="{{ $pos->id }}">
                    {{ $pos->requiredPosition?->title ?? ('#'.$pos->id) }}
                    @if($pos->center)
                        — {{ $pos->center->getTranslation('name', $locale) }}
                    @endif
                </option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.position_id') }}</x-error-span>
    </div>

    {{-- Contract type --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="contract_type_id">{{ trans_choice('general.contract-type', 1) }}</x-forms.input-label>
        <x-forms.select-input id="contract_type_id" wire:model="form.contract_type_id" class="mt-1">
            <option value="">{{ __('general.select') }}</option>
            @foreach($types as $t)
                <option value="{{ $t->id }}">{{ $t->getTranslation('name', $locale) }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.contract_type_id') }}</x-error-span>
    </div>

    {{-- Department --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="department_id">{{ trans_choice('general.department', 1) }}</x-forms.input-label>
        <x-forms.select-input id="department_id" wire:model="form.department_id" class="mt-1">
            <option value="">{{ __('general.select') }}</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}">{{ $d->name }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.department_id') }}</x-error-span>
    </div>

    {{-- Job level --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="job_level_id">{{ trans_choice('general.job-level', 1) }}</x-forms.input-label>
        <x-forms.select-input id="job_level_id" wire:model="form.job_level_id" class="mt-1">
            <option value="">{{ __('general.select') }}</option>
            @foreach($levels as $l)
                <option value="{{ $l->id }}">{{ $l->getTranslation('name', $locale) }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.job_level_id') }}</x-error-span>
    </div>

</div>
