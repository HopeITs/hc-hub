@php
    $children = collect($node['children']);
    $positionId = $node['id'];
    $hasChildren = $children->isNotEmpty();
    $startsOpen = ! ($this->collapsed[$positionId] ?? false);
    // A subtree can be worth opening even when its own card is quiet, so the
    // chip carries the open seats waiting below it rather than just a count.
    $waiting = $node['subtreeOpenSeats'] - $node['openSeats'];

    $isRootLevel = $isRootLevel ?? false;

    // The connectors are the organisation chart's, not a second system invented
    // for this page. There a level's rung is a single `border-t` on the row that
    // holds the children — one border on one box, so it cannot drift or break
    // apart — and each child raises a short stem to meet it. The earlier version
    // here drew each rung as two absolutely-positioned halves per column, which
    // had to agree with each other about where a card's centre was and stopped
    // agreeing as soon as cards had different widths.
    $trunk = 'w-px bg-neutral-300';
    $rung = 'border-t border-neutral-300';
    $gap = $compact ? 'gap-2' : 'gap-4';
    $stem = $compact ? 'h-4' : 'h-5';

    // A report that manages nobody is a leaf. Leaves pool into a wrapped grid
    // below the branch row instead of stretching the level into one endless
    // line — again the organisation chart's own answer to a wide team.
    $branches = $children->filter(fn (array $child): bool => count($child['children']) > 0)->values();
    $leaves = $children->reject(fn (array $child): bool => count($child['children']) > 0)->values();
@endphp

<section wire:key="staffing-node-{{ $positionId }}"
         x-data="{ open: @js($startsOpen) }"
         x-on:staffing-expand-all.window="open = true"
         x-on:staffing-collapse-all.window="open = false"
         class="relative flex flex-col items-center {{ $isRootLevel ? '' : ($compact ? 'pt-4' : 'pt-5') }}">

    {{-- The stem that rises to the rung of the level above. --}}
    @unless ($isRootLevel)
        <span aria-hidden="true" class="absolute start-1/2 top-0 {{ $stem }} {{ $trunk }}"></span>
    @endunless

    <x-staffing-card :node="$node" :compact="$compact" :view="$view">
        @if ($hasChildren)
            <button type="button" wire:click="toggle({{ $positionId }})" x-on:click="open = ! open"
                    :aria-expanded="open" data-no-pan
                    :title="open ? @js(__('general.collapse')) : @js(__('general.expand'))"
                    class="mt-2 flex w-full items-center justify-between gap-2 rounded-control bg-neutral-100 px-2 py-1.5 text-caption font-bold text-secondary-medium transition hover:bg-secondary-soft hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-1">
                <span class="flex items-center gap-1.5">
                    {{ trans_choice('recruitment.team_staffing.reports_count', $children->count(), ['count' => $children->count()]) }}
                    @if ($waiting > 0)
                        <span class="flex min-w-4 items-center justify-center rounded-full bg-primary px-1 text-caption font-bold text-white"
                              title="{{ __('recruitment.team_staffing.open_seats_below', ['count' => $waiting]) }}">
                            {{ $waiting }}
                        </span>
                    @endif
                </span>
                <svg class="size-4 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            </button>

            {{-- Children live inside their manager's box, which is what makes the
                 containment of a subtree visible without a single extra line. --}}
            <div x-show="open" x-cloak x-collapse>
                <div class="relative mt-4 flex w-full flex-col items-center {{ $gap }} {{ $rung }}">
                    @if ($branches->isNotEmpty())
                        <div class="flex items-start justify-center {{ $gap }}">
                            @foreach ($branches as $child)
                                @include('livewire.recruitment.requests.partials.team-staffing-node', [
                                    'node' => $child,
                                    'compact' => $compact,
                                    'view' => $view,
                                    'isRootLevel' => false,
                                ])
                            @endforeach
                        </div>
                    @endif

                    @if ($leaves->isNotEmpty())
                        <div class="relative flex w-full flex-col items-center pt-4">
                            <span aria-hidden="true" class="absolute start-1/2 top-0 {{ $stem }} {{ $trunk }}"></span>

                            <div class="flex w-full flex-wrap content-start items-start justify-center {{ $gap }} rounded-surface border border-neutral-100 bg-neutral-50/40 {{ $compact ? 'max-w-[36rem] p-2' : 'max-w-[44rem] p-3' }}">
                                @foreach ($leaves as $child)
                                    @include('livewire.recruitment.requests.partials.team-staffing-node', [
                                        'node' => $child,
                                        'compact' => $compact,
                                        'view' => $view,
                                        'isRootLevel' => true,
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </x-staffing-card>
</section>
