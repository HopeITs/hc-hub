@props([
    /* Livewire property (or list of properties) the page form is bound to, e.g. "form" or ['form', 'tiers']. */
    'watch',
])

@php
    /*
     * Marks a page form as worth asking about: leaving the page while one of
     * these properties differs from what it held when the page opened asks
     * "Discard your changes?" first (resources/js/unsaved.js). It draws
     * nothing, so it can sit anywhere inside the page's Livewire component.
     */
    $paths = array_values((array) $watch);
@endphp

<span hidden x-data="unsavedGuard(@js($paths))" data-unsaved-guard data-unsaved-question="{{ __('common.discard_changes_question') }}"></span>
