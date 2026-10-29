@props(['href' => null])

@php
    // media-request .btn .btn-g: grey fill that lightens on hover.
    $baseClasses = 'min-h-11 py-2 px-[18px] text-body font-bold text-neutral-800 bg-neutral-200 hover:opacity-85
                    cursor-pointer rounded-control border-[1.5px] border-transparent transition duration-150 ease-in-out
                    flex justify-center items-center gap-2 active:scale-[0.98]
                    focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-800
                    disabled:opacity-45 disabled:cursor-not-allowed';
@endphp

@if ($href)
    {{-- Internal links swap pages via wire:navigate; pass :wire:navigate="false" to opt out. --}}
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $baseClasses, 'wire:navigate' => true]) }}>
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
