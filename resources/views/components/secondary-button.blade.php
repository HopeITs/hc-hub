@props(['href' => null])

@php
    $baseClasses = 'py-2 px-4 text-sm font-semibold text-gray-700 bg-gray-200 hover:bg-gray-300
                    cursor-pointer rounded-md shadow-sm transition duration-150 ease-in-out
                    flex justify-center items-center disabled:opacity-60 disabled:cursor-not-allowed';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClasses]) }}>
        {{ $slot }}
    </a>
@else
    <button wire:loading.attr="disabled"
        {{ $attributes->merge([
            'class' => $baseClasses,
            'type'  => 'button',
        ]) }}>
        {{ $slot }}
    </button>
@endif
