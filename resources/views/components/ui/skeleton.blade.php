{{--
    Placeholder shapes shown while content loads. Variants: row (a list line), card (a tile), panel (a titled block).

        <x-ui.skeleton variant="row" :count="5" />

    Standard wire:loading recipe, used everywhere so loading looks the same:
      1. Content that is replaced (first load, tab switch): put the skeleton in a wrapper with
         wire:loading.delay wire:target="the,actions" and hide the real content with wire:loading.remove (same target).
      2. Content that stays (filter, sort, page): keep it and dim it with wire:loading.class.delay="opacity-60"
         (x-ui.data-table already does this on its rows).
      3. Always .delay, so quick responses never flash; the pulse stops under prefers-reduced-motion.
    The shapes are aria-hidden; a screen-reader-only "Loading…" line carries the state (aria-busy on the wrapper).
--}}
@props([
    'variant' => 'row',
    'count' => 1,
])

@php
    $bar = 'rounded-inset bg-secondary-soft dark:bg-surface-3';
    $items = max(1, (int) $count);
@endphp

<div role="status" aria-busy="true" data-ui-skeleton data-variant="{{ $variant }}" {{ $attributes->class('animate-pulse motion-reduce:animate-none') }}>
    <span class="sr-only">{{ __('common.loading') }}</span>

    <div aria-hidden="true" @class(['grid gap-3', 'sm:grid-cols-2 lg:grid-cols-3' => $variant === 'card'])>
        @for ($i = 0; $i < $items; $i++)
            @if ($variant === 'card')
                <div class="space-y-3 rounded-xl border border-board-line bg-board p-4">
                    <div class="{{ $bar }} h-4 w-1/3"></div>
                    <div class="{{ $bar }} h-8 w-1/2"></div>
                    <div class="{{ $bar }} h-3 w-2/3"></div>
                </div>
            @elseif ($variant === 'panel')
                <div class="space-y-4 rounded-xl border border-board-line bg-board p-4">
                    <div class="{{ $bar }} h-5 w-1/4"></div>
                    <div class="space-y-2">
                        <div class="{{ $bar }} h-3 w-full"></div>
                        <div class="{{ $bar }} h-3 w-11/12"></div>
                        <div class="{{ $bar }} h-3 w-3/4"></div>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3 py-1">
                    <div class="{{ $bar }} size-8 shrink-0 rounded-full"></div>
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="{{ $bar }} h-3 w-2/5"></div>
                        <div class="{{ $bar }} h-3 w-3/5"></div>
                    </div>
                </div>
            @endif
        @endfor
    </div>
</div>
