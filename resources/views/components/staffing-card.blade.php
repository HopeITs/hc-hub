@props(['node', 'compact' => false])

@php
    $position = $node['position'];
    $openSeats = $node['openSeats'];
    $filled = (int) $position->exist_emp;
    $capacity = (int) $position->max_emp;
    $isRoot = $node['isRoot'];
    $passthrough = $node['passthrough'] ?? false;

    $available = collect($node['actions'])->filter(fn (array $action): bool => $action['available']);
    $blocked = collect($node['actions'])->filter(
        fn (array $action): bool => ! $action['available'] && $action['reason'] !== null,
    );

    // The frame leads with the one thing the manager is scanning for: a seat
    // standing open. Red edge means "you can act here"; a request already in
    // flight mutes it back down, because the action has been taken.
    $accent = match (true) {
        $openSeats > 0 && $node['requests']->isEmpty() => 'bg-primary',
        $node['requests']->isNotEmpty() => 'bg-secondary-light',
        default => 'bg-neutral-200',
    };

    // The cards themselves are the organisation chart's own ID cards, unchanged.
    // Everything around them is the staffing frame.
    $minWidth = $compact ? 'min-w-36' : 'min-w-44';
@endphp

<div
    x-data="{ menu: false }"
    x-on:click.outside="menu = false"
    x-on:keydown.escape.window="menu = false"
    {{-- No data-no-pan here on purpose: the frame stays draggable so the canvas
         can be panned from anywhere. Only the menu opts out. --}}
    class="relative {{ $minWidth }} shrink-0 rounded-surface border bg-white shadow-e1 transition
           {{ $isRoot ? 'border-secondary-dark' : 'border-neutral-200' }}
           {{ $passthrough ? 'opacity-60 hover:opacity-100' : '' }}
           {{ $compact ? 'p-2' : 'p-3' }}"
>
    <span class="absolute inset-y-0 start-0 w-1 rounded-s-surface {{ $accent }}" aria-hidden="true"></span>

    {{-- ---------- Seats ---------- --}}
    <div class="mb-2 flex items-center justify-between gap-2 ps-1">
        @if ($capacity > 0 && $capacity <= 6)
            <div class="flex items-center gap-1" role="img"
                 aria-label="{{ __('recruitment.team_staffing.seats_label', ['filled' => $filled, 'capacity' => $capacity]) }}">
                @for ($seat = 0; $seat < $capacity; $seat++)
                    @if ($seat < $filled)
                        <span class="size-2 rounded-full bg-secondary-medium" aria-hidden="true"></span>
                    @else
                        <span class="size-2 rounded-full border border-dashed border-primary bg-primary/15" aria-hidden="true"></span>
                    @endif
                @endfor
            </div>
        @else
            <span class="text-caption font-bold tabular-nums text-secondary-medium">
                {{ $filled }}<span class="text-secondary-light">/{{ $capacity }}</span>
            </span>
        @endif

        @if ($isRoot)
            <span class="rounded-full bg-secondary-dark px-2 py-0.5 text-caption font-bold uppercase tracking-wide text-white">
                {{ __('recruitment.team_staffing.you') }}
            </span>
        @elseif ($openSeats > 0)
            <span class="rounded-full bg-primary/10 px-2 py-0.5 text-caption font-bold text-primary">
                {{ __('recruitment.team_staffing.open_capacity_count', ['count' => $openSeats]) }}
            </span>
        @endif
    </div>

    {{-- ---------- The organisation chart's own card ----------
         One card per position, never one per holder: a frame that grows with
         its headcount would make every row a different width, and the chart
         is read by comparing positions, not people. The seat strip above
         already carries how many of them there are. --}}
    <div class="flex justify-center">
        @php($holder = $position->activeContracts->first()?->employee)
        @if ($holder)
            <x-org-node :position="$position" :employee="$holder"
                        :is-manager="count($node['children']) > 0" :compact="$compact" />
        @else
            <x-org-empty-node :position="$position" :compact="$compact" />
        @endif
    </div>

    @if ($filled > 1)
        <p class="mt-1.5 text-center text-caption font-semibold text-secondary-light">
            {{ __('recruitment.team_staffing.more_holders', ['count' => $filled - 1]) }}
        </p>
    @endif

    {{-- ---------- Request in flight ---------- --}}
    @if ($node['requests']->isNotEmpty())
        <div class="mt-2 space-y-1">
            @foreach ($node['requests'] as $request)
                @php($status = \App\Enums\RecruitmentRequestStatus::getLocalizedName((int) $request->status))
                {{-- Literal classes: the colour name comes from the enum, and a
                     class built by interpolation is never seen by the scanner. --}}
                @php($dot = match ($status['color']) {
                    'primary' => 'bg-primary',
                    'secondary-medium' => 'bg-secondary-medium',
                    default => 'bg-secondary-light',
                })
                <a href="{{ route('recruitment.requests.show', ['requestId' => $request->id]) }}" wire:navigate
                   class="flex items-center gap-1.5 rounded-control bg-neutral-50 px-2 py-1 text-caption font-semibold text-secondary-medium transition hover:bg-secondary-soft hover:text-primary">
                    <span class="size-1.5 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>
                    <span class="truncate">
                        {{ \App\Enums\RecruitmentRequestType::getLocalizedName((int) $request->request_type)['value'] }}
                        <span class="text-secondary-light">·</span> {{ $status['value'] }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- ---------- Action trigger ---------- --}}
    @if ($available->isNotEmpty() || $blocked->isNotEmpty())
        <button type="button" x-on:click="menu = ! menu" :aria-expanded="menu" aria-haspopup="menu"
                @disabled($available->isEmpty())
                class="mt-2 flex w-full items-center justify-between gap-1 rounded-control px-2 py-1.5 text-caption font-bold transition
                       {{ $available->isNotEmpty()
                           ? 'bg-primary text-white hover:opacity-90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-1'
                           : 'cursor-not-allowed bg-neutral-100 text-secondary-light' }}">
            <span class="flex min-w-0 items-center gap-1">
                <svg class="size-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                <span class="truncate">
                    {{ $available->isNotEmpty()
                        ? __('recruitment.team_staffing.open_request')
                        : __('recruitment.team_staffing.no_action_available') }}
                </span>
            </span>
            @if ($available->isNotEmpty())
                <svg class="size-3 shrink-0 transition-transform" :class="menu ? 'rotate-180' : ''"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
            @endif
        </button>
    @endif

    {{-- ---------- Action menu ---------- --}}
    @if ($available->isNotEmpty())
        <div x-show="menu" x-cloak x-transition.origin.top data-no-pan role="menu"
             class="absolute inset-x-0 top-full z-30 mt-1 min-w-48 overflow-hidden rounded-surface border border-neutral-200 bg-white text-start shadow-e3">
            @foreach ($available as $action)
                <a href="{{ $action['url'] }}" wire:navigate role="menuitem"
                   class="flex items-center gap-2 border-b border-neutral-100 px-3 py-2.5 text-small font-semibold text-secondary-dark transition last:border-b-0 hover:bg-secondary-soft hover:text-primary">
                    <span class="size-1.5 shrink-0 rounded-full bg-primary" aria-hidden="true"></span>
                    {{ $action['label'] }}
                </a>
            @endforeach

            @if ($blocked->isNotEmpty())
                <div class="border-t border-neutral-200 bg-neutral-50 px-3 py-2">
                    <p class="text-caption font-bold uppercase tracking-wide text-secondary-light">
                        {{ __('recruitment.team_staffing.unavailable_actions') }}
                    </p>
                    <ul class="mt-1.5 space-y-1">
                        @foreach ($blocked as $blockedAction)
                            <li class="text-caption leading-snug text-secondary-light">
                                <span class="font-bold text-secondary-medium">{{ $blockedAction['label'] }}:</span>
                                {{ $blockedAction['reason'] }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif
</div>
