@props(['node', 'collapsed' => []])

{{-- One position in the team outline, then its reports (recursively). --}}
@php
    $position = $node['position'];
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $isCollapsed = (bool) ($collapsed[$node['id']] ?? false);
    $holders = $position->activeContracts->map(fn ($contract) => $contract->employee?->name)->filter()->unique()->values();
    $capacity = (int) $position->max_emp;
    $filled = (int) $position->exist_emp;
    $available = collect($node['actions'])->filter(fn ($action) => $action['available']);
@endphp

<li wire:key="node-{{ $node['id'] }}" data-staffing-node="{{ $node['id'] }}">
    <div @class(['flex flex-wrap items-start gap-3 rounded-md border border-panel-border p-3', 'opacity-70' => $node['passthrough'], 'bg-secondary-soft/40 dark:bg-surface-2' => $node['isRoot']])>
        @if ($node['children'] !== [])
            <button type="button" wire:click="toggle({{ $node['id'] }})" class="mt-0.5 rounded p-0.5 hover:bg-secondary-soft dark:hover:bg-surface-3" aria-expanded="{{ $isCollapsed ? 'false' : 'true' }}"
                aria-label="{{ $isCollapsed ? __('hr_recruitment.team_staffing.show_team') : __('hr_recruitment.team_staffing.hide_team') }}">
                <flux:icon :name="$isCollapsed ? 'chevron-right' : 'chevron-down'" variant="mini" class="size-4 rtl:[&:is([data-flux-icon])]:-scale-x-100" />
            </button>
        @else
            <span class="w-5" aria-hidden="true"></span>
        @endif

        <div class="min-w-0 flex-1">
            <p class="flex flex-wrap items-center gap-2">
                <span class="font-semibold" dir="auto">{{ $position->title }}</span>
                @if ($node['isRoot'])
                    <span class="rounded-md bg-secondary-soft px-1.5 text-xs font-semibold dark:bg-surface-3">{{ __('hr_recruitment.team_staffing.you') }}</span>
                @endif
                @if ($node['openSeats'] > 0)
                    <span class="rounded-md border border-primary px-1.5 text-xs font-semibold text-primary dark:border-brand dark:text-brand-text">{{ __('hr_recruitment.team_staffing.open_capacity_count', ['count' => $node['openSeats']]) }}</span>
                @endif
            </p>
            <p class="text-xs {{ $muted }}">
                {{ collect([$position->program?->name, $position->center?->name ?? $position->city?->name])->filter()->implode(' · ') }}
                · <span class="tabular-nums">{{ __('hr_recruitment.team_staffing.seats_label', ['filled' => $filled, 'capacity' => $capacity]) }}</span>
                @if ($node['children'] !== [] && $isCollapsed && $node['subtreeOpenSeats'] > $node['openSeats'])
                    · {{ __('hr_recruitment.team_staffing.open_seats_below', ['count' => $node['subtreeOpenSeats'] - $node['openSeats']]) }}
                @endif
            </p>
            <p class="mt-1 text-sm" dir="auto">
                @if ($holders->isEmpty())
                    <span class="{{ $muted }}">{{ __('hr_recruitment.team_staffing.no_current_holder') }}</span>
                @else
                    {{ $holders->take(3)->implode('، ') }}
                    @if ($holders->count() > 3)<span class="{{ $muted }}"> {{ __('hr_recruitment.team_staffing.more_holders', ['count' => $holders->count() - 3]) }}</span>@endif
                @endif
            </p>
            @foreach ($node['requests'] as $request)
                <a href="{{ route('hr.recruitment.requests.show', ['requestId' => $request->id]) }}" wire:navigate class="mt-1 me-2 inline-flex items-center gap-1 text-xs underline-offset-2 hover:underline" wire:key="node-request-{{ $request->id }}">
                    <bdi class="tabular-nums">{{ $request->request_number }}</bdi> · {{ \Modules\Hr\Support\Recruitment\Labels::enum('RecruitmentRequestStatus', $request->status) }}
                </a>
            @endforeach
        </div>

        @if ($node['actions'] !== [])
            <flux:dropdown position="bottom" align="end">
                <x-ui.button tone="secondary" size="sm" icon-trailing="chevron-down">{{ __('hr_recruitment.team_staffing.open_request') }}</x-ui.button>
                <flux:menu>
                    @foreach ($node['actions'] as $type => $action)
                        @if ($action['available'])
                            <flux:menu.item :href="$action['url']" wire:navigate>{{ $action['label'] }}</flux:menu.item>
                        @else
                            <flux:menu.item disabled title="{{ $action['reason'] }}">{{ $action['label'] }}</flux:menu.item>
                        @endif
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        @endif
    </div>

    @if ($node['children'] !== [] && ! $isCollapsed)
        <ul class="mt-2 space-y-2 border-s border-panel-border ps-4 sm:ps-6" role="group">
            @foreach ($node['children'] as $child)
                <x-hr::recruitment.staffing-node :node="$child" :collapsed="$collapsed" />
            @endforeach
        </ul>
    @endif
</li>
