@php($locale = app()->getLocale())

<div class="mb-4 md:col-span-12">
    <div class="p-3 text-body bg-white border rounded-surface border-secondary-light text-secondary-dark">
        <strong class="text-primary">@lang('general.note'):</strong>
        @lang('general.note-text1') —
        @lang('general.note-text2').
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-12 sm:gap-6">

    <div class="md:col-span-6">
        <x-forms.input-label for="city_id">{{ trans_choice('general.city', 1) }}</x-forms.input-label>
        <x-forms.client-select2 id="city_id" x-ref="city">
            <option value="">{{ __('general.select') }}</option>
            @foreach ($cities as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </x-forms.client-select2>
        <x-error-span>{{ $errors->first('city_id') }}</x-error-span>
    </div>

    <div class="md:col-span-6">
        <x-forms.input-label for="program_id">{{ trans_choice('general.program', 2) }}</x-forms.input-label>
        <x-forms.client-select2 id="program_id" x-ref="program" disabled>
            <option value="">{{ __('general.select') }}</option>
        </x-forms.client-select2>
        <x-error-span>{{ $errors->first('program_id') }}</x-error-span>
    </div>

    <div class="md:col-span-6">
        <x-forms.input-label for="employee_id" required>{{ trans_choice('general.employee', 2) }}</x-forms.input-label>
        <x-forms.client-select2 id="employee_id" x-ref="employee" disabled>
            <option value="">{{ __('general.select') }}</option>
        </x-forms.client-select2>
        <x-error-span>{{ $errors->first('form.employee_id') }}</x-error-span>
    </div>

    <div class="md:col-span-6">
        <x-forms.input-label for="center_id">{{ trans_choice('general.centers', 1) }}</x-forms.input-label>
        <x-forms.client-select2 id="center_id" x-ref="center" disabled>
            <option value="">{{ __('general.select') }}</option>
        </x-forms.client-select2>
        <x-error-span>{{ $errors->first('center_id') }}</x-error-span>
    </div>

    <div class="md:col-span-6">
        <x-forms.input-label for="position_id" required>
            {{ trans_choice('general.positions', 1) }}
        </x-forms.input-label>

        <x-forms.client-select2 id="position_id" x-ref="position" disabled>
            <option value="">{{ __('general.select') }}</option>
        </x-forms.client-select2>

        @error('form.position_id')
            <x-error-span>{{ $message }}</x-error-span>
        @enderror
    </div>

    <div class="md:col-span-6">
        <x-forms.input-label
            for="contract_type_id" required>{{ trans_choice('general.contract-type', 1) }}</x-forms.input-label>
        <x-forms.client-select2 id="contract_type_id" x-ref="contractType">
            <option value="">{{ __('general.select') }}</option>
            @foreach ($types as $t)
                <option value="{{ $t->id }}">{{ $t->name }}</option>
            @endforeach
        </x-forms.client-select2>
        <x-error-span>{{ $errors->first('form.contract_type_id') }}</x-error-span>
    </div>

    <div
        x-cloak
        x-show="loading"
        class="md:col-span-12"
        role="status"
        aria-live="polite"
    >
        <div class="inline-flex min-h-11 items-center gap-2 rounded-surface bg-neutral-100 px-3 py-2 text-small font-semibold text-secondary-medium dark:bg-neutral-800 dark:text-neutral-200">
            <x-icons.spinner class="size-4 animate-spin text-primary" />
            <span x-text="loadingText"></span>
        </div>
    </div>

</div>
