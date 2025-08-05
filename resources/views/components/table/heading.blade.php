@props([
    'sortable' => false,
    'direction' => null,
])

@php
    $clickAction = $attributes->get('wire:click');
@endphp

<th class="text-sm px-5 py-2.5 bg-gray-200 rtl:text-right text-left text-gray-900 border-secondary-medium">
    @if (! $sortable)
        <span class="text-xs leading-4 font-medium text-secondary-medium uppercase tracking-wider">
            {{ $slot }}
        </span>
    @else
        <button
            type="button"
            {{ $attributes->only('wire:click') }}
            class="group flex items-center gap-1 text-xs leading-4 font-medium cursor-pointer hover:text-primary transition">
            <span>{{ $slot }}</span>
            <span>
                @if ($direction === 'asc')
                    {{-- Up arrow --}}
                    <svg class="size-3 text-gray-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8 10 4 4 4-4"/>
                    </svg>
                @elseif ($direction === 'desc')
                    {{-- Down arrow --}}
                    <svg class="size-3 text-gray-800 dark:text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16 14-4-4-4 4"/>
                    </svg>
                @else
                    {{-- Hover arrow --}}
                    <svg class="size-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300 text-secondary-dark"
                         xmlns="http://www.w3.org/2000/svg" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8 10 4 4 4-4"/>
                    </svg>
                @endif
            </span>
        </button>
    @endif
</th>
