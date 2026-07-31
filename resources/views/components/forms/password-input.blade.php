@props([
    'id' => null,
    'label' => '',
    'model' => null,
    'placeholder' => '',
    'autocomplete' => 'new-password',
    'required' => false,
    'errorKey' => null,
])

@php
    use Illuminate\Support\Str;

    $inputId = $id ?? Str::slug($model ?? 'password');
    $hasModel = !empty($attributes->wire('model'));

    // Mirrors <x-forms.input>: point the field at its message so the failure is
    // announced on focus rather than only painted underneath.
    $errorField = $errorKey ?? ($attributes->wire('model')->value() ?: $model);
    $hasError = $errorField && $errors->has($errorField);
@endphp

<div class="space-y-1">

    {{-- Label --}}
    @if ($label)
        <x-forms.input-label for="{{ $inputId }}" :value="$label" :required="$required" />
    @endif

    {{-- Input + Toggle --}}
    <div class="relative">
        <input
            id="{{ $inputId }}"
            type="password"
            placeholder="{{ $placeholder }}"
            autocomplete="{{ $autocomplete }}"
            @if ($required) required aria-required="true" @endif
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
            {{ $hasModel ? $attributes->merge(['wire:model' => $model]) : $attributes }}
            class="w-full py-2 px-3 text-sm text-gray-900 placeholder-gray-400 rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-secondary-light focus:border-primary transition {{ $label ? 'h-[38px]' : '' }}"
        />

        {{--
            Toggle Button using data attribute.

            aria-label is required here: the button's only content is a
            decorative icon font, which gives a screen reader nothing to
            announce. aria-controls ties it to the field it acts on.
        --}}
        <button
            type="button"
            tabindex="-1"
            data-password-toggle="{{ $inputId }}"
            aria-label="{{ __('general.a11y.toggle-password') }}"
            aria-controls="{{ $inputId }}"
            class="absolute top-2.5 end-3 text-gray-400 hover:text-gray-600 cursor-pointer"
        >
            <i id="{{ $inputId }}_icon" aria-hidden="true" class="fa-regular fa-eye-slash text-sm pointer-events-none"></i>
        </button>
    </div>

    {{-- Error --}}
    @if ($hasModel)
        <x-forms.input-error :field="$attributes->wire('model')" :input-id="$inputId" />
    @endif
</div>
