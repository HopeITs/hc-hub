@props([
    'id' => null,
    'label' => '',
    'model' => null,
    'placeholder' => '',
    'autocomplete' => 'new-password',
])

@php
    use Illuminate\Support\Str;

    $inputId = $id ?? Str::slug($model ?? 'password');
    $hasModel = !empty($attributes->wire('model'));
@endphp

<div class="space-y-1">

    {{-- Label --}}
    @if ($label)
        <x-forms.input-label for="{{ $inputId }}" :value="$label" />
    @endif

    {{-- Input + Toggle --}}
    <div class="relative">
        <input
            id="{{ $inputId }}"
            type="password"
            placeholder="{{ $placeholder }}"
            autocomplete="{{ $autocomplete }}"
            {{ $hasModel ? $attributes->merge(['wire:model' => $model]) : $attributes }}
            class="w-full py-2 px-3 text-sm text-gray-900 placeholder-gray-400 rounded-md border border-gray-300 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500 transition {{ $label ? 'h-[38px]' : '' }}"
        />

        {{-- Toggle Button using data attribute --}}
        <button
            type="button"
            tabindex="-1"
            data-password-toggle="{{ $inputId }}"
            class="absolute top-2.5 end-3 text-gray-400 hover:text-gray-600 cursor-pointer"
        >
            <i id="{{ $inputId }}_icon" class="fa-regular fa-eye-slash text-sm pointer-events-none"></i>
        </button>
    </div>

    {{-- Error --}}
    @if ($hasModel)
        <x-forms.input-error :field="$attributes->wire('model')" />
    @endif
</div>
