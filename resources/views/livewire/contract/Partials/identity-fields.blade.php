@php($locale = app()->getLocale())

<div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6">

    {{-- Employee --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="employee_id">@lang('general.employee')</x-forms.input-label>
        <x-forms.select-input id="employee_id" wire:model="form.employee_id" class="mt-1">
            <option value="">{{ __('general.select') }}</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}">
                    {{
                        (method_exists($e, 'getTranslatableAttributes') && in_array('name', $e->getTranslatableAttributes(), true))
                            ? $e->getTranslation('name', $locale)
                            : ($e->name ?? '')
                    }}
                </option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.employee_id') }}</x-error-span>
    </div>

    {{-- Position --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="position_id">{{ trans_choice('general.positions', 1) }}</x-forms.input-label>
        <x-forms.select-input id="position_id" wire:model="form.position_id" class="mt-1">
            <option value="">{{ __('general.select') }}</option>
            @foreach($positions as $pos)
                <option value="{{ $pos->id }}">
                    {{ $pos->title ?? $pos->requiredPosition?->title ?? ('#'.$pos->id) }}
                    @php($center = $pos->center ?? null)
                    @if($center)
                        {{-- show center name (translated when available) --}}
                        — {{
                            (method_exists($center, 'getTranslatableAttributes') && in_array('name', $center->getTranslatableAttributes(), true))
                                ? $center->getTranslation('name', $locale)
                                : ($center->name ?? '')
                        }}
                    @endif
                </option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.position_id') }}</x-error-span>

        {{-- Derived info from the selected position --}}
        @php($selected = $positions->firstWhere('id', (int) ($form->position_id ?? 0)))
    </div>

    {{-- Contract type --}}
    <div class="md:col-span-6">
        <x-forms.input-label for="contract_type_id">{{ trans_choice('general.contract-type', 1) }}</x-forms.input-label>
        <x-forms.select-input id="contract_type_id" wire:model="form.contract_type_id" class="mt-1">
            <option value="">{{ __('general.select') }}</option>
            @foreach($types as $t)
                <option value="{{ $t->id }}">
                    {{
                        (method_exists($t, 'getTranslatableAttributes') && in_array('name', $t->getTranslatableAttributes(), true))
                            ? $t->getTranslation('name', $locale)
                            : ($t->name ?? '')
                    }}
                </option>
            @endforeach
        </x-forms.select-input>
        <x-error-span class="mt-1 block text-sm">{{ $errors->first('form.contract_type_id') }}</x-error-span>
    </div>
</div>
