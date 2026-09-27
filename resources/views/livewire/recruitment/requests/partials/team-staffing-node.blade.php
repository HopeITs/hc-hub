@php
    $children = $node['children'];
    $positionId = $node['id'];
    $hasChildren = count($children) > 0;
    $startsOpen = ! ($this->collapsed[$positionId] ?? false);
    // A subtree can be worth opening even when its own card is quiet, so the
    // chip carries the open seats waiting below it rather than just a count.
    $waiting = $node['subtreeOpenSeats'] - $node['openSeats'];

    $isFirst = $isFirst ?? true;
    $isLast = $isLast ?? true;
    $isRootLevel = $isRootLevel ?? false;
    $line = 'bg-neutral-300';
    $stem = $compact ? 'h-5' : 'h-7';
    $pad = $compact ? 'px-1.5' : 'px-3';
@endphp

<div wire:key="staffing-node-{{ $positionId }}"
     class="relative flex flex-col items-center {{ $pad }} {{ $isRootLevel ? '' : ($compact ? 'pt-5' : 'pt-7') }}">

    {{-- Incoming connectors: two half-bars meeting across the column padding,
         plus the stem that drops onto this card. Drawn per column so the
         horizontal run always ends on the first and last card's centre. --}}
    @unless ($isRootLevel)
        @unless ($isFirst)
            <span class="absolute top-0 start-0 end-1/2 h-px {{ $line }}" aria-hidden="true"></span>
        @endunless
        @unless ($isLast)
            <span class="absolute top-0 start-1/2 end-0 h-px {{ $line }}" aria-hidden="true"></span>
        @endunless
        <span class="absolute top-0 start-1/2 w-px {{ $stem }} {{ $line }}" aria-hidden="true"></span>
    @endunless

    <div x-data="{ open: @js($startsOpen) }"
         x-on:staffing-expand-all.window="open = true"
         x-on:staffing-collapse-all.window="open = false"
         class="flex flex-col items-center">

        <x-staffing-card :node="$node" :compact="$compact" />

        {{-- ---------- Branch toggle ---------- --}}
        @if ($hasChildren)
            <button type="button" wire:click="toggle({{ $positionId }})" x-on:click="open = ! open"
                    :aria-expanded="open"
                    class="relative z-10 mt-1.5 flex items-center gap-1.5 rounded-full border border-neutral-200 bg-white px-2.5 py-1 text-caption font-bold text-secondary-medium shadow-e1 transition hover:border-secondary-light hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-1">
                <span>{{ trans_choice('recruitment.team_staffing.reports_count', count($children), ['count' => count($children)]) }}</span>
                @if ($waiting > 0)
                    <span class="flex min-w-4 items-center justify-center rounded-full bg-primary px-1 text-caption font-bold text-white"
                          title="{{ __('recruitment.team_staffing.open_seats_below', ['count' => $waiting]) }}">
                        {{ $waiting }}
                    </span>
                @endif
                <svg class="size-3 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </button>
        @endif

        {{-- ---------- Children ---------- --}}
        @if ($hasChildren)
            <div x-show="open" x-cloak x-collapse class="flex flex-col items-center">
                <span class="{{ $stem }} w-px {{ $line }}" aria-hidden="true"></span>
                <div class="flex items-start justify-center">
                    @foreach ($children as $index => $child)
                        @include('livewire.recruitment.requests.partials.team-staffing-node', [
                            'node' => $child,
                            'compact' => $compact,
                            'isFirst' => $index === 0,
                            'isLast' => $index === count($children) - 1,
                            'isRootLevel' => false,
                        ])
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
