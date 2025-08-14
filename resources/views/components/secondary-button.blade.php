@props(['href' => null])

@if ($href)
    <a href="{{ $href }}"
        {{ $attributes->merge([
            'class' => 'w-full py-2 px-4 text-sm font-semibold text-gray-700 bg-gray-200 hover:bg-gray-300 cursor-pointer rounded-md shadow-sm transition duration-150 ease-in-out flex justify-center items-center',
        ]) }}>
        {{ $slot }}
    </a>
@else
    <button wire:loading.attr="disabled"
        {{ $attributes->merge([
            'class' => 'w-full py-2 px-4 text-sm font-semibold text-gray-700 bg-gray-200 hover:bg-gray-300 cursor-pointer rounded-md shadow-sm transition duration-150 ease-in-out flex justify-center items-center',
            'type' => 'button',
        ]) }}>
        {{ $slot }}
    </button>
@endif
