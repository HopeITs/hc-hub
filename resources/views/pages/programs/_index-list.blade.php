{{--
    The one list of programs/index: a row per program with its name, strategy and
    manager, one status word with the reasons behind it, and how many projects and
    annual plans it has. The primary action is Open (or Assign when the program is
    not linked yet). Included by ⚡index.blade.php, which defines $visiblePrograms.
--}}
<x-ui.panel>
    <ul class="divide-y divide-secondary-soft dark:divide-line" data-test="program-list">
        @foreach ($visiblePrograms as $program)
            @php
                $overdueProjects = (int) $program->overdue_projects_count;
                $riskyProjects = (int) $program->risky_projects_count;
                $unfundedProjects = (int) ($program->unfunded_projects_count ?? 0);
                $health = $program->health;
                $status = $program->status;
                $tone = match ($status) {
                    'attention' => $health === 'critical' ? 'text-status-critical' : 'text-status-warn',
                    'ontrack' => 'text-status-good',
                    default => 'text-secondary-dark dark:text-ink',
                };
                $dot = match ($status) {
                    'attention' => $health === 'critical' ? 'bg-status-critical' : 'bg-status-warn',
                    'ontrack' => 'bg-status-good',
                    default => 'bg-secondary-light dark:bg-ink-faint',
                };
                $needsLink = $program->strategy === null || $program->manager === null;
            @endphp

            <li class="grid gap-3 px-4 py-4 md:grid-cols-[minmax(0,1fr)_minmax(14rem,20rem)_auto] md:items-center" wire:key="program-row-{{ $program->id }}" data-status="{{ $status }}" data-program-health="{{ $health }}">
                <div class="min-w-0">
                    <h2 class="line-clamp-2 break-words text-base font-bold leading-snug text-secondary-dark dark:text-ink"><bdi>{{ $program->name }}</bdi></h2>
                    <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 type-meta text-secondary-medium dark:text-ink-muted">
                        @if ($program->strategy)
                            <span><bdi>{{ $this->strategyLabel($program->strategy) }}</bdi></span>
                        @else
                            <span class="font-semibold text-status-warn" data-program-unlinked>{{ __('programs.not_linked_yet') }}</span>
                        @endif
                        <span aria-hidden="true">·</span>
                        @if ($program->manager)
                            <span><bdi>{{ $program->manager->name }}</bdi></span>
                        @else
                            <span class="font-semibold text-status-warn">{{ __('programs.no_manager_yet') }}</span>
                        @endif
                    </p>
                </div>

                <div class="min-w-0">
                    <p class="inline-flex items-center gap-1.5 text-sm font-semibold {{ $tone }}">
                        <span aria-hidden="true" class="size-2 shrink-0 rounded-full {{ $dot }}"></span>
                        {{ __('programs.status_'.$status) }}
                    </p>
                    <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs">
                        @if ($program->projects_count === 0)
                            <span class="text-secondary-medium dark:text-ink-muted">{{ __('programs.card_no_projects') }}</span>
                        @elseif ($health === 'good')
                            <span class="text-secondary-medium dark:text-ink-muted">{{ __('programs.card_nothing_late') }}</span>
                        @else
                            @if ($overdueProjects > 0)<span class="font-semibold text-status-critical">{{ trans_choice('programs.card_overdue', $overdueProjects, ['count' => $overdueProjects]) }}</span>@endif
                            @if ($riskyProjects > 0)<span class="font-semibold text-status-critical">{{ trans_choice('programs.card_risks', $riskyProjects, ['count' => $riskyProjects]) }}</span>@endif
                            @if ($unfundedProjects > 0)<span class="font-semibold text-status-warn">{{ trans_choice('programs.card_unfunded', $unfundedProjects, ['count' => $unfundedProjects]) }}</span>@endif
                        @endif
                    </p>
                    <p class="mt-1 type-meta tabular-nums text-secondary-medium dark:text-ink-muted">
                        {{ trans_choice('programs.count_projects', $program->projects_count, ['count' => $program->projects_count]) }}
                        · {{ trans_choice('programs.count_plans', $program->annual_plans_count, ['count' => $program->annual_plans_count]) }}
                    </p>
                </div>

                <div class="flex items-center gap-1 md:justify-end">
                    @if ($needsLink)
                        @can('programs.update')
                            <x-ui.button size="sm" icon="link" class="min-h-11 md:min-h-9" wire:click="openEditModal({{ $program->id }})" :aria-label="__('programs.assign_name_short', ['name' => $program->name])" data-test="assign-program-{{ $program->id }}">
                                {{ __('programs.assign') }}
                            </x-ui.button>
                        @endcan
                    @endif

                    <x-ui.button :tone="$needsLink && auth()->user()->can('programs.update') ? 'ghost' : 'primary'" size="sm" icon="arrows-pointing-out" class="min-h-11 md:min-h-9" :href="route('programs.show', $program)" wire:navigate :aria-label="__('programs.open_name', ['name' => $program->name])" data-test="open-program-{{ $program->id }}">
                        {{ __('programs.open_program') }}
                    </x-ui.button>

                    @can('programs.update')
                        <flux:dropdown align="end">
                            <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" class="min-h-11 md:min-h-9" :aria-label="__('programs.more_actions_for_name', ['name' => $program->name])" />
                            <flux:menu>
                                <flux:menu.item icon="pencil-square" wire:click="openEditModal({{ $program->id }})" :aria-label="__('common.edit_name', ['name' => $program->name])">{{ __('programs.edit_assignment') }}</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    @endcan
                </div>
            </li>
        @endforeach
    </ul>
</x-ui.panel>
