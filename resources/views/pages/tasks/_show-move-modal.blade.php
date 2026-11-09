{{--
    "Move to" on the task page (MovesTask): one list of every place inside the
    task's program — the program itself, each project, and each project's
    milestones under it — with a search box, the current place marked, and
    the subtasks moving along. Only shown for a top-level task of a program.
--}}
@php
    $destinations = $this->moveDestinations;
    $currentTarget = $this->currentMoveTarget();
    $optionClasses = 'flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm text-secondary-dark hover:bg-secondary-soft/60 has-[:checked]:bg-secondary-soft has-[:checked]:font-semibold dark:text-ink dark:hover:bg-white/[0.04] dark:has-[:checked]:bg-surface-3';
    $radioClasses = 'size-4 shrink-0 accent-primary dark:accent-brand';
    $currentBadge = '<span class="ms-auto shrink-0 rounded-full bg-secondary-soft px-2 py-0.5 text-[11px] font-semibold text-secondary-medium dark:bg-surface-2 dark:text-ink-muted">'.e(__('tasks.move_current')).'</span>';
@endphp

<x-ui.modal
    wire:model="showMoveModal"
    size="md"
    :title="__('tasks.move_task')"
    :description="__('tasks.move_hint')"
    submit="moveTask"
    close="closeMoveModal"
>
    @if ($showMoveModal && $destinations !== null)
        <div x-data="{ find: '' }" class="grid gap-3" data-test="task-move-dialog">
            <x-ui.input type="search" size="sm" icon="magnifying-glass" x-model="find" :placeholder="__('tasks.move_search')" :aria-label="__('tasks.move_search')" />

            <div class="-mx-1 grid max-h-80 gap-1 overflow-y-auto overscroll-contain px-1" role="radiogroup" aria-label="{{ __('tasks.move_task') }}">
                {{-- The program itself. --}}
                <label class="{{ $optionClasses }}" x-show="find === '' || {{ Js::from(mb_strtolower($destinations['program']['name'].' '.__('tasks.move_program_itself'))) }}.includes(find.trim().toLowerCase())" data-move-target="program">
                    <input type="radio" wire:model="moveTarget" value="program" class="{{ $radioClasses }}" />
                    <span class="min-w-0">
                        <span class="block truncate">{{ __('tasks.move_program_itself') }}</span>
                        <span class="block truncate text-xs font-normal text-secondary-medium dark:text-ink-muted">{{ $destinations['program']['name'] }}</span>
                    </span>
                    @if ($currentTarget === 'program')
                        {!! $currentBadge !!}
                    @endif
                </label>

                {{-- Each project, then its milestones under it. A project stays visible while any of its lines matches. --}}
                @foreach ($destinations['projects'] as $project)
                    @php
                        $projectText = mb_strtolower($project['name']);
                        $milestoneTexts = collect($project['milestones'])->map(fn (array $milestone): string => mb_strtolower($milestone['name']))->all();
                    @endphp
                    <div class="mt-2 grid gap-1" wire:key="move-project-{{ $project['id'] }}"
                        x-show="find === '' || [{{ Js::from($projectText) }}, ...{{ Js::from($milestoneTexts) }}].some((text) => text.includes(find.trim().toLowerCase()))">
                        <p class="px-3 pt-1 text-[11px] font-bold tracking-wide text-secondary-medium uppercase dark:text-ink-muted">{{ $project['name'] }}</p>

                        <label class="{{ $optionClasses }}" data-move-target="project:{{ $project['id'] }}">
                            <input type="radio" wire:model="moveTarget" value="project:{{ $project['id'] }}" class="{{ $radioClasses }}" />
                            <span class="min-w-0 truncate">{{ __('tasks.move_whole_project') }}</span>
                            @if ($currentTarget === 'project:'.$project['id'])
                                {!! $currentBadge !!}
                            @endif
                        </label>

                        @foreach ($project['milestones'] as $milestone)
                            <label class="{{ $optionClasses }} ps-6" wire:key="move-milestone-{{ $milestone['id'] }}" data-move-target="milestone:{{ $milestone['id'] }}"
                                x-show="find === '' || {{ Js::from($projectText) }}.includes(find.trim().toLowerCase()) || {{ Js::from(mb_strtolower($milestone['name'])) }}.includes(find.trim().toLowerCase())">
                                <input type="radio" wire:model="moveTarget" value="milestone:{{ $milestone['id'] }}" class="{{ $radioClasses }}" />
                                <span class="min-w-0 truncate" title="{{ $milestone['name'] }}">{{ $milestone['name'] }}</span>
                                @if ($currentTarget === 'milestone:'.$milestone['id'])
                                    {!! $currentBadge !!}
                                @endif
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <x-slot:actions>
        <x-ui.button type="submit" tone="primary" data-test="task-move-confirm">{{ __('tasks.move_here') }}</x-ui.button>
    </x-slot:actions>
</x-ui.modal>
