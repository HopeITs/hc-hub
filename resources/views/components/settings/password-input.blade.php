@props([
    'id',
    'label',
    'wireModel' => null,
    'autocomplete' => '',
    'error' => [],
])

@php
    $field = $attributes->wire('model.defer') ?? $wireModel;
@endphp

<div>
    <x-settings.input-label :for="$id" :value="$label" />

    <div class="relative">
        <input
            id="{{ $id }}"
            type="password"
            autocomplete="{{ $autocomplete }}"
            {{ $attributes->merge([
                'class' => 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-md focus:ring-primary focus:border-primary block w-full pe-10',
                'wire:model.defer' => $field,
            ]) }}
        />

        <button type="button"
                onclick="togglePasswordVisibility('{{ $id }}')"
                class="absolute inset-y-0 end-0 pe-3 flex items-center text-gray-500 hover:text-gray-700 dark:text-gray-400"
                tabindex="-1">
            <i class="ti ti-eye-off text-lg" id="{{ $id }}_icon"></i>
        </button>
    </div>

    <x-settings.input-error :field="$field" :messages="$error" class="mt-2" />
</div>
