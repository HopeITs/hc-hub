@props([

    'value' => null,
    'theme' => 'default', // 'default' for auth, 'settings' for profile/settings
])

@php
    $baseClass = match($theme) {
        'settings' => 'block text-sm mb-3 font-medium text-secondary-medium',
        default    => 'block mb-1 text-sm font-medium text-secondary-medium',
    };
@endphp

<label {{ $attributes->merge(['class' => $baseClass]) }}>
    {{ $value ?? $slot }}
</label>
