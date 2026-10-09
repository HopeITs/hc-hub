@props(['node', 'compact' => false, 'view' => 'staff'])

@php
    $position = $node['position'];
    $openSeats = $node['openSeats'];
    $filled = (int) $position->exist_emp;
    $capacity = (int) $position->max_emp;
    $isRoot = $node['isRoot'];
    $passthrough = $node['passthrough'] ?? false;
    $holders = $position->activeContracts->map(fn ($contract) => $contract->employee)->filter()->values();
    $available = collect($node['actions'])->filter(fn (array $action): bool => $action['available']);
    $isStaff = $view === 'staff';
    // Open and nobody has asked yet: the one state worth a red marker. A request in flight takes it back to grey.
    $needsAction = $openSeats > 0 && $node['requests']->isEmpty();
    $width = $isStaff ? '' : ($compact ? 'w-44' : 'w-52');
    $focus = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
@endphp

{{-- One border per card. The root carries a dark top edge so "you" reads from across the chart. --}}
<div x-data="{ menu: false }" x-on:keydown.escape="menu = false" x-on:click.outside="menu = false"
     @class([
         'relative flex shrink-0 flex-col rounded-surface border bg-white shadow-e1 transition duration-150 hover:shadow-e2 motion-reduce:transition-none',
         'border-neutral-200',
         'border-t-[3px] border-t-secondary-dark' => $isRoot,
         'opacity-60 hover:opacity-100' => $passthrough,
         'p-3' => $compact,
         'p-4' => ! $compact,
         'min-h-44' => ! $isStaff && $compact,
         'min-h-52' => ! $isStaff && ! $compact,
     ])>
  <div class="{{ $width }} mx-auto flex flex-1 flex-col">
    <div class="flex items-center justify-between gap-3 pb-3">
        @if ($capacity > 0 && $capacity <= 8)
            <div class="flex items-center gap-1" role="img" aria-label="{{ __('recruitment.team_staffing.seats_label', ['filled' => $filled, 'capacity' => $capacity]) }}">
                @for ($seat = 0; $seat < $capacity; $seat++)
                    <span aria-hidden="true" @class([
                        'size-2 rounded-full',
                        'bg-secondary-dark' => $seat < $filled,
                        'border border-dashed border-primary' => $seat >= $filled && $needsAction,
                        'border border-dashed border-neutral-400' => $seat >= $filled && ! $needsAction,
                    ])></span>
                @endfor
                <span class="ms-1 text-caption tabular-nums text-secondary-medium" aria-hidden="true">{{ $filled }}/{{ $capacity }}</span>
            </div>
        @else
            <span class="text-caption font-semibold tabular-nums text-secondary-dark" aria-label="{{ __('recruitment.team_staffing.seats_label', ['filled' => $filled, 'capacity' => $capacity]) }}">
                {{ $filled }}<span class="text-secondary-light">/{{ $capacity }}</span>
            </span>
        @endif

        @if ($isRoot)
            <span class="rounded-inset bg-secondary-dark px-2 py-0.5 text-caption font-semibold text-white">{{ __('recruitment.team_staffing.you') }}</span>
        @elseif ($openSeats > 0)
            <span @class([
                'inline-flex items-center gap-1.5 rounded-inset px-2 py-0.5 text-caption font-semibold',
                'bg-white text-primary ring-1 ring-inset ring-primary/40' => $needsAction,
                'bg-neutral-100 text-secondary-dark' => ! $needsAction,
            ])>
                @if ($needsAction)<span class="size-1.5 rounded-full bg-primary" aria-hidden="true"></span>@endif
                {{ __('recruitment.team_staffing.open_capacity_count', ['count' => $openSeats]) }}
            </span>
        @endif
    </div>

    @if ($isStaff)
        <div class="flex flex-wrap items-start justify-center gap-2">
            @foreach ($holders as $person)
                <x-org-node :key="'holder-'.$person->getKey()" :position="$position" :employee="$person"
                            :is-manager="count($node['children']) > 0" :compact="$compact" />
            @endforeach
            @for ($seat = 0; $seat < max($openSeats, $holders->isEmpty() ? 1 : 0); $seat++)
                <x-org-empty-node :key="'seat-'.$node['id'].'-'.$seat" :position="$position" :compact="$compact" />
            @endfor
        </div>
    @else
        {{-- Positions view: title, programme, holder. No photo; this face is read by comparing posts. --}}
        <div class="space-y-1">
            <p class="min-h-[2.75em] text-body font-semibold leading-snug text-secondary-dark" dir="auto">{{ $position->title }}</p>
            @if ($position->program?->name)
                <p class="truncate text-caption text-secondary-medium" dir="auto" title="{{ $position->program->name }}">{{ $position->program->name }}</p>
            @endif
            <p class="flex items-center gap-2 pt-1 text-small" dir="auto">
                @if ($holders->isNotEmpty())
                    @php($initials = collect(preg_split('/\s+/u', trim((string) $holders->first()->name), -1, PREG_SPLIT_NO_EMPTY) ?: [])->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?')
                    <span aria-hidden="true" class="flex size-6 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-[10px] font-semibold text-secondary-dark">{{ $initials }}</span>
                    <span class="truncate text-secondary-dark">{{ $holders->first()->name }}</span>
                    @if ($holders->count() > 1)<span class="shrink-0 text-caption text-secondary-medium">+{{ $holders->count() - 1 }}</span>@endif
                @else
                    <span aria-hidden="true" class="size-6 shrink-0 rounded-full border border-dashed border-neutral-400"></span>
                    <span class="text-secondary-medium">{{ __('general.vacant-position') }}</span>
                @endif
            </p>
        </div>
    @endif

    @if ($node['requests']->isNotEmpty())
        <ul class="mt-3 space-y-1">
            @foreach ($node['requests'] as $request)
                @php($status = \App\Enums\RecruitmentRequestStatus::getLocalizedName((int) $request->status))
                <li wire:key="staffing-request-{{ $request->id }}">
                    <a href="{{ route('recruitment.requests.show', ['requestId' => $request->id]) }}" wire:navigate data-no-pan
                       class="flex min-h-9 items-center gap-2 rounded-control bg-neutral-50 px-2.5 text-caption text-secondary-dark transition hover:bg-selection {{ $focus }}">
                        <flux:icon.document-text variant="micro" class="size-3.5 shrink-0 text-secondary-medium" aria-hidden="true" />
                        <span class="min-w-0 flex-1 truncate">
                            <span class="font-semibold">{{ \App\Enums\RecruitmentRequestType::getLocalizedName((int) $request->request_type)['value'] }}</span>
                            <span class="text-secondary-medium">· {{ $status['value'] }}</span>
                        </span>
                        <flux:icon.chevron-right variant="micro" class="size-3.5 shrink-0 text-secondary-light rtl:rotate-180" aria-hidden="true" />
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Starting a request: a quiet outline button. It turns solid only on the cards that need it, so the chart shows where to act without a wall of red. --}}
    @if ($available->isNotEmpty())
        <div class="mt-auto pt-3">
            <button type="button" x-on:click="menu = ! menu" :aria-expanded="menu" data-no-pan
                    @class([
                        'flex min-h-11 w-full cursor-pointer items-center justify-center gap-1.5 rounded-control px-3 text-caption font-semibold shadow-e1 transition duration-150 motion-reduce:transition-none sm:min-h-9',
                        $focus,
                        'bg-secondary-medium text-white hover:opacity-90' => $needsAction,
                        'border border-neutral-200 bg-white text-secondary-dark hover:bg-selection' => ! $needsAction,
                    ])>
                <flux:icon.plus variant="micro" class="size-3.5 shrink-0" aria-hidden="true" />
                <span class="whitespace-nowrap">{{ __('recruitment.team_staffing.open_request') }}</span>
                <flux:icon.chevron-down variant="micro" class="size-3.5 shrink-0 transition-transform duration-200 motion-reduce:transition-none" ::class="menu ? 'rotate-180' : ''" aria-hidden="true" />
            </button>
            <ul x-show="menu" x-cloak x-collapse data-no-pan class="mt-1.5 overflow-hidden rounded-control border border-neutral-200 bg-white py-1 shadow-e2">
                @foreach ($available as $type => $action)
                    <li wire:key="staffing-action-{{ $node['id'] }}-{{ $type }}">
                        <a href="{{ $action['url'] }}" wire:navigate
                           class="flex min-h-11 items-center gap-2 px-3 text-caption font-medium text-secondary-dark transition hover:bg-selection hover:text-secondary-dark sm:min-h-9 focus-visible:bg-selection focus-visible:outline-none">
                            <span class="leading-snug">{{ $action['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
  </div>

    {{ $slot }}
</div>
