@props(['href' => null])

@php
    $baseClasses = 'py-2 px-4 text-sm font-semibold text-white bg-primary hover:opacity-90 cursor-pointer
                    rounded-md shadow-sm transition duration-150 ease-in-out flex justify-center items-center';
@endphp

@if ($href)
    <a href="{{ $href }}"
            {{ $attributes->merge(['class' => $baseClasses]) }}>
        {{ $slot }}
    </a>
@else
    <button wire:loading.attr="disabled"
            {{ $attributes->merge([
                'class' => $baseClasses,
                'type' => 'submit',
            ]) }}>
        {{ $slot }}
    </button>
@endif
