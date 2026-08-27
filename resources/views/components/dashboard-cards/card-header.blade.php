@props([
    'title',
    'count' => null,
])

<header {{ $attributes->class('flex min-h-16 shrink-0 items-center justify-start gap-4 px-6 pt-2 text-left') }}>
    <div class="flex min-w-0 items-center gap-2">
        <h2 class="truncate whitespace-nowrap text-base font-semibold leading-none text-secondary-dark">
            {{ $title }}
        </h2>

        @if($count !== null)
            <span class="text-base font-medium leading-none text-secondary-light">
                {{ $count }}
            </span>
        @endif
    </div>

    @isset($actions)
        <div class="ms-auto min-w-0">
            {{ $actions }}
        </div>
    @endisset
</header>
