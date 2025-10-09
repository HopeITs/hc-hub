@php($locale = app()->getLocale())

<div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6">

    {{-- City --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="city_id">{{ trans_choice('general.city', 1) }}</x-forms.input-label>
        <x-forms.select-input id="city_id" wire:model.live="city_id" class="mt-1">
            <option value="">{{ __('general.select') }}</option>
            @foreach($cities as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('city_id') }}</x-error-span>
    </div>

    {{-- Employee (filtered by city) --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="employee_id">{{ trans_choice('general.employee', 2) }}</x-forms.input-label>
        <x-forms.select-input id="employee_id"
                              wire:model="form.employee_id"
                              class="mt-1"
                              :disabled="!$city_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}">{{ $e->name }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.employee_id') }}</x-error-span>
    </div>

    {{-- Program (available in selected city) --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="program_id">{{ trans_choice('general.program', 2) }}</x-forms.input-label>
        <x-forms.select-input id="program_id"
                              wire:model.live="program_id"
                              class="mt-1"
                              :disabled="!$city_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($programs as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('program_id') }}</x-error-span>
    </div>

    {{-- Center (in city and hosting positions for selected program) --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="center_id">{{ trans_choice('general.centers', 1) }}</x-forms.input-label>
        <x-forms.select-input id="center_id"
                              wire:model.live="center_id"
                              class="mt-1"
                              :disabled="!$city_id || !$program_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($centers as $center)
                <option value="{{ $center->id }}">{{ $center->name }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('center_id') }}</x-error-span>
    </div>

    {{-- Position (filtered by city + program + center) --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="position_id">{{ trans_choice('general.positions', 1) }}</x-forms.input-label>
        <x-forms.select-input id="position_id"
                              wire:model="form.position_id"
                              class="mt-1"
                              :disabled="!$city_id || !$program_id || !$center_id">
            <option value="">{{ __('general.select') }}</option>
            @foreach($positions as $pos)
                <option value="{{ $pos->id }}">{{ $pos->title ?? ('#'.$pos->id) }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.position_id') }}</x-error-span>
    </div>

    {{-- Contract type --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="contract_type_id">{{ trans_choice('general.contract-type', 1) }}</x-forms.input-label>
        <x-forms.select-input id="contract_type_id"
                              wire:model="form.contract_type_id"
                              class="mt-1">
            <option value="">{{ __('general.select') }}</option>
            @foreach($types as $t)
                <option value="{{ $t->id }}">{{ $t->name }}</option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.contract_type_id') }}</x-error-span>
    </div>

</div>
