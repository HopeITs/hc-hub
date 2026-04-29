@php($locale = app()->getLocale())

<div class="md:col-span-12 mb-4">
    <div class="bg-white border border-secondary-light text-secondary-dark text-sm rounded-lg p-3">
        <strong class="text-primary">@lang('general.note'):</strong>
        @lang('general.note-text1') —
        @lang('general.note-text2').
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6">

    <div class="md:col-span-6">
        <x-forms.input-label for="city_id">{{ trans_choice('general.city', 1) }}</x-forms.input-label>
        <x-forms.select2-input id="city_id" wire:model.live="city_id" >
            <option value="">{{ __('general.select') }}</option>
            @foreach($cities as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </x-forms.select2-input>
        <x-error-span >{{ $errors->first('city_id') }}</x-error-span>
    </div>



    <div class="md:col-span-6" wire:key="container-program-city-{{ $city_id }}" >
        <x-forms.input-label for="program_id">{{ trans_choice('general.program', 2) }}</x-forms.input-label>
        <x-forms.select2-input id="program_id" wire:model.live="program_id" :disabled="!$city_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($programs as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </x-forms.select2-input>
        <x-error-span >{{ $errors->first('program_id') }}</x-error-span>
    </div>

    <div class="md:col-span-6">
        <x-forms.input-label for="employee_id">{{ trans_choice('general.employee', 2) }}</x-forms.input-label>
        <x-forms.select2-input wire:model="form.employee_id" id="employee_id">
            <option value="">{{ __('general.select') }}</option>
        
            @foreach($employees as $e)
                <option value="{{ $e->id }}">
                    {{ $e->name }}
                </option>
            @endforeach
        </x-forms.select2-input>
        <x-error-span >{{ $errors->first('form.employee_id') }}</x-error-span>
    </div>

    <div class="md:col-span-6" wire:key="center-for-city-{{ $city_id }}-prog-{{ $program_id }}">
        <x-forms.input-label for="center_id">{{ trans_choice('general.centers', 1) }}</x-forms.input-label>
        <x-forms.select2-input id="center_id" wire:model.live="center_id"
                               :disabled="!$city_id || !$program_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($centers as $center)
                <option value="{{ $center->id }}">{{ $center->name }}</option>
            @endforeach
        </x-forms.select2-input>
        <x-error-span >{{ $errors->first('center_id') }}</x-error-span>
    </div>

    <div class="md:col-span-6" wire:key="pos-for-city-{{ $city_id }}-prog-{{ $program_id }}">
        <x-forms.input-label for="position_id">
            {{ trans_choice('general.positions', 1) }}
        </x-forms.input-label>

        <x-forms.select2-input
            id="position_id"
            wire:model.live="form.position_id"

            :disabled="!$city_id || !$program_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($positions as $pos)
                <option value="{{ $pos->id }}">
                    {{ $pos->title ?? ('#' . $pos->id) }}
                </option>
            @endforeach
        </x-forms.select2-input>

        @error('form.position_id')
        <x-error-span >{{ $message }}</x-error-span>
        @enderror
    </div>

    <div class="md:col-span-6">
        <x-forms.input-label for="contract_type_id">{{ trans_choice('general.contract-type', 1) }}</x-forms.input-label>
        <x-forms.select2-input id="contract_type_id" wire:model.live="form.contract_type_id" >
            <option value="">{{ __('general.select') }}</option>
            @foreach($types as $t)
                <option value="{{ $t->id }}">{{ $t->name }}</option>
            @endforeach
        </x-forms.select2-input>
        <x-error-span >{{ $errors->first('form.contract_type_id') }}</x-error-span>
    </div>

</div>
