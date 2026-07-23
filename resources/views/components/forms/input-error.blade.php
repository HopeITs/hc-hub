@props([
    'field' => null,
    'theme' => 'default',
])

@php
    $baseClass = match($theme) {
        'settings' => 'text-body text-red-600 dark:text-red-400 mt-1',
        default    => 'text-primary text-body mt-1',
    };
@endphp

@if(is_string($field))
    @error($field)
        <p {{ $attributes->merge(['class' => $baseClass]) }}>
            {{ $message }}
        </p>
    @enderror
@endif
