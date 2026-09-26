@php
    $position = $node['position'];
    $children = $node['children'];
    $isCollapsed = $this->collapsed[$position->id] ?? false;
    $employeeNames = $position->activeContracts
        ->map(fn ($contract) => $contract->employee?->name)
        ->filter()
        ->values();
@endphp

<article wire:key="team-position-{{ $position->id }}" class="space-y-3">
    <div class="rounded-surface border border-neutral-200 bg-white p-4 shadow-e1">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="truncate text-lg font-semibold text-secondary-dark">{{ $position->title }}</h3>
                    @if ($node['depth'] === 0)
                        <span class="rounded-full bg-secondary-soft px-2.5 py-1 text-xs font-semibold text-secondary-medium">
                            {{ __('recruitment.team_staffing.you') }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-secondary-medium">
                    {{ $position->program?->name ?? '—' }}
                    <span class="px-1 text-secondary-light">·</span>
                    {{ $position->department?->name ?? '—' }}
                    @if ($position->city?->name || $position->center?->name)
                        <span class="px-1 text-secondary-light">·</span>
                        {{ $position->city?->name ?? $position->center?->name }}
                    @endif
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                    <span class="rounded-full bg-secondary-soft px-2.5 py-1 font-semibold text-secondary-dark">
                        {{ __('recruitment.team_staffing.capacity') }}:
                        {{ $position->exist_emp }} / {{ $position->max_emp }}
                    </span>
                    <span class="rounded-full bg-primary/10 px-2.5 py-1 font-semibold text-primary">
                        {{ __('recruitment.team_staffing.open_capacity_count', ['count' => max(0, $position->max_emp - $position->exist_emp)]) }}
                    </span>
                    @if ($node['openVacancies']->isNotEmpty())
                        <span class="rounded-full bg-primary/10 px-2.5 py-1 font-semibold text-primary">
                            {{ __('recruitment.team_staffing.open_vacancies', ['count' => $node['openVacancies']->count()]) }}
                        </span>
                    @endif
                </div>
                @if ($employeeNames->isNotEmpty())
                    <p class="mt-3 text-sm text-secondary-medium">
                        <span class="font-semibold text-secondary-dark">{{ __('recruitment.team_staffing.current_holders') }}:</span>
                        {{ $employeeNames->join(', ') }}
                    </p>
                @else
                    <p class="mt-3 text-sm font-medium text-primary">{{ __('recruitment.team_staffing.no_current_holder') }}</p>
                @endif
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2 lg:max-w-sm lg:justify-end">
                @if ($children)
                    <button type="button" wire:click="toggle({{ $position->id }})"
                            class="rounded-control border border-neutral-300 px-3 py-2 text-sm font-semibold text-secondary-dark transition hover:border-secondary-light hover:bg-neutral-50"
                            aria-expanded="{{ $isCollapsed ? 'false' : 'true' }}">
                        {{ $isCollapsed ? __('recruitment.team_staffing.show_team') : __('recruitment.team_staffing.hide_team') }}
                    </button>
                @endif
                <div class="flex flex-wrap gap-2">
                    @foreach ($node['actions'] as $type => $action)
                        @if ($action['available'])
                            <a href="{{ $action['url'] }}" wire:navigate
                               class="rounded-control bg-primary px-3 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                                {{ $action['label'] }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        @php($blockedActions = collect($node['actions'])->filter(fn (array $action): bool => ! $action['available'] && $action['reason'] !== null))
        @if ($blockedActions->isNotEmpty())
            <details class="mt-4 border-t border-neutral-100 pt-3 text-sm text-secondary-medium">
                <summary class="cursor-pointer font-semibold text-secondary-dark">{{ __('recruitment.team_staffing.unavailable_actions') }}</summary>
                <ul class="mt-2 grid gap-1 ps-4 sm:grid-cols-2">
                    @foreach ($blockedActions as $blockedAction)
                        <li class="list-disc">
                            <span class="font-semibold">{{ $blockedAction['label'] }}:</span>
                            {{ $blockedAction['reason'] }}
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif

        @if ($node['requests']->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2 border-t border-neutral-100 pt-3">
                @foreach ($node['requests'] as $request)
                    <a href="{{ route('recruitment.requests.show', ['requestId' => $request->id]) }}" wire:navigate
                       class="rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1.5 text-xs font-semibold text-secondary-dark transition hover:border-primary hover:text-primary">
                        {{ \App\Enums\RecruitmentRequestType::getLocalizedName((int) $request->request_type)['value'] }}
                        · {{ \App\Enums\RecruitmentRequestStatus::getLocalizedName((int) $request->status)['value'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($children && ! $isCollapsed)
        <div class="ms-4 space-y-3 border-s-2 border-neutral-200 ps-4 sm:ms-8 sm:ps-6">
            @foreach ($children as $child)
                @include('livewire.recruitment.requests.partials.team-staffing-node', ['node' => $child])
            @endforeach
        </div>
    @endif
</article>
