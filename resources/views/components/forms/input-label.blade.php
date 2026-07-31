@props([

    'value' => null,
    'theme' => 'default', // 'default' for auth, 'settings' for profile/settings
    'required' => false,  // adds a red asterisk for required fields
])

@php
    $baseClass = match($theme) {
        'settings' => 'block text-sm mb-3 font-medium text-secondary-medium',
        default    => 'block mb-1 text-sm font-medium text-secondary-medium',
    };
@endphp

<label {{ $attributes->merge(['class' => $baseClass]) }}>
    {{ $value ?? $slot }}
    @if ($required)
        {{--
            The asterisk is decorative — most screen readers either skip a bare
            "*" or read it as "star", neither of which conveys the requirement.
            The visually hidden word is what actually gets announced.
        --}}
        <span class="text-red-600 ltr:ml-0.5 rtl:mr-0.5" aria-hidden="true">*</span>
        <span class="sr-only">({{ __('general.a11y.required-field') }})</span>
    @endif
</label>
