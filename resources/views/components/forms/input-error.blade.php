@props([
    'field',
    'theme' => 'default', // 'default' for auth, 'settings' for settings/profile
])

@php
    $baseClass = match($theme) {
        'settings' => 'text-sm text-red-600 dark:text-red-400 mt-1',
        default    => 'text-primary text-sm mt-1',
    };
@endphp

@error($field)
<p {{ $attributes->merge(['class' => $baseClass]) }}>
    {{ $message }}
</p>
@enderror
