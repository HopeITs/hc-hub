@props(['title' => null, 'subtitle' => null, 'count' => null, 'as' => 'section', 'clip' => true, 'loadingTarget' => null])

@php
    $tag = in_array($as, ['section', 'aside', 'article', 'div', 'ol', 'ul'], true) ? $as : 'section';
@endphp

{{-- The bordered card (W-29): radius and border from the panel tokens. clip=false keeps sticky children working (overflow-hidden would trap them). --}}
<{{ $tag }} data-panel {{ $attributes->class(['rounded-panel border border-panel-border bg-white shadow-card dark:bg-surface-1 dark:shadow-raised', 'overflow-hidden' => $clip, 'relative' => $loadingTarget])->merge($loadingTarget ? ['wire:loading.delay.attr' => 'aria-busy', 'wire:target' => $loadingTarget] : []) }}>
    @if ($title !== null || isset($toolbar))
        <div class="flex min-h-12 flex-wrap items-center justify-between gap-2 border-b border-panel-border px-4 py-3">
            <div class="min-w-0">
                @if ($title !== null)
                    <div class="flex items-center gap-2">
                        <p class="truncate text-base font-bold text-secondary-dark dark:text-ink">{{ $title }}</p>
                        @if ($count !== null)
                            <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-secondary-soft px-2 py-0.5 text-xs font-semibold tabular-nums text-secondary-dark dark:bg-surface-2 dark:text-ink">{{ $count }}</span>
                        @endif
                    </div>
                @endif
                @if ($subtitle !== null)
                    <p class="truncate text-xs text-secondary-medium dark:text-ink-muted">{{ $subtitle }}</p>
                @endif
            </div>
            {{ $toolbar ?? '' }}
        </div>
    @endif

    {{ $slot }}

    @if ($loadingTarget)
        {{-- Opt-in skeleton overlay (recipe in x-ui.skeleton): covers the stale content after the delay, so the panel never changes height. --}}
        <div class="hidden" wire:loading.delay.class.remove="hidden" wire:target="{{ $loadingTarget }}" data-ui-panel-loading>
            <x-ui.skeleton variant="row" :count="3" class="absolute inset-0 z-10 overflow-hidden bg-white p-4 dark:bg-surface-1" />
        </div>
    @endif

    @isset($footer)
        <div class="border-t border-panel-border px-4 pb-3">{{ $footer }}</div>
    @endisset
</{{ $tag }}>
