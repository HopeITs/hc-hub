@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'private' => false,
])

<section
    x-data="{ open: false }"
    class="rounded-2xl border {{ $private ? 'border-primary' : 'border-neutral-200' }} bg-white p-4 shadow-sm sm:rounded-3xl sm:p-6"
>
    <button
        type="button"
        class="flex w-full items-center gap-3 text-start sm:mb-5 sm:cursor-default"
        x-on:click="if (window.innerWidth < 640) open = !open"
        x-bind:aria-expanded="open"
    >
        @if($icon)
            <div class="rounded-xl bg-primary p-2 text-white sm:rounded-2xl sm:p-3">
                <x-dynamic-component :component="$icon" class="h-4 w-4 !me-0 sm:h-6 sm:w-6"/>
            </div>
        @endif

        <div class="min-w-0 flex-1">
            <h2 class="text-lg font-bold text-primary sm:text-xl">{{ $title }}</h2>
            @if($subtitle)
                <p class="mt-0.5 text-xs text-secondary-light sm:mt-1 sm:text-sm">{{ $subtitle }}</p>
            @endif
        </div>

        <svg
            class="h-4 w-4 shrink-0 text-primary transition-transform duration-200 sm:hidden"
            x-bind:class="{ 'rotate-180': open }"
            viewBox="0 0 20 20"
            fill="none"
            aria-hidden="true"
        >
            <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </button>

    <div x-cloak x-show="open" x-collapse class="mt-4 sm:hidden">
        {{ $slot }}
    </div>

    <div class="hidden sm:block">
        {{ $slot }}
    </div>
</section>
