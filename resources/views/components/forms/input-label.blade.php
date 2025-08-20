@props([
    'for' => null,
    'value' => null,
    'theme' => 'default', // 'default' for auth, 'settings' for profile/settings
])

@php
    $baseClass = match($theme) {
        'settings' => 'block text-sm mb-3 font-medium text-gray-700 dark:text-gray-300',
        default    => 'block mb-1 text-sm font-medium text-gray-700',
    };
@endphp

<label for="{{ $for }}" {{ $attributes->merge(['class' => $baseClass]) }}>
    {{ $value ?? $slot }}
</label>
