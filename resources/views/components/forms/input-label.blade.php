@props([

    'value' => null,
    'theme' => 'default', // 'default' for auth, 'settings' for profile/settings
    'required' => false,  // adds a red asterisk for required fields
])

@php
    $baseClass = match($theme) {
        'settings' => 'block text-body mb-3 font-medium text-secondary-medium',
        default    => 'block mb-1 text-body font-medium text-secondary-medium',
    };
@endphp

<label {{ $attributes->merge(['class' => $baseClass]) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="text-red-600 ltr:ml-1 rtl:mr-1" aria-hidden="true">*</span>
    @endif
</label>
