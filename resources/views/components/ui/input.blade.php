@props(['label' => null, 'icon' => null, 'size' => null, 'clearable' => false, 'type' => 'text'])

@php
    /*
     * Alias of x-ui.form-input, kept so existing call sites keep working: one text
     * input look app-wide. A magnifier icon or type="search" selects the search
     * variant, size="sm" the compact height, and a missing label is read from
     * aria-label or placeholder and kept for screen readers only.
     */
    $accessibleName = $label ?? $attributes->get('aria-label') ?? $attributes->get('placeholder') ?? '';
@endphp

<x-ui.form-input
    :label="$accessibleName"
    :hide-label="$label === null"
    :type="$type"
    :variant="$icon === 'magnifying-glass' || $type === 'search' ? 'search' : null"
    :compact="$size === 'sm'"
    :clearable="$clearable"
    {{ $attributes->except('aria-label') }}
/>
