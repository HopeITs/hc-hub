@use('App\Enums\TaskStatus')
@use('App\Models\Project')
@use('App\Models\Task')
        <x-ui.panel :title="__('common.tasks')" :count="$this->tasks->count()" :subtitle="__('programs.tasks_across_the_program_its_projects_and_their_milestones')">
            <x-slot:toolbar>
                @can('create', App\Models\Task::class)<x-ui.button tone="primary" size="sm" icon="plus" wire:click="openAddTaskModal" data-test="add-program-task-button">{{ __('tasks.add_task') }}</x-ui.button>@endcan
            </x-slot:toolbar>

            @if ($this->tasks->isEmpty())
                <x-ui.empty-state icon="clipboard-document-list" :heading="__('common.no_tasks_yet')" :body="__('programs.tasks_for_this_program_will_appear_here')">
                    <x-slot:action>
                        @can('create', App\Models\Task::class)<x-ui.button size="sm" tone="primary" icon="plus" wire:click="openAddTaskModal">{{ __('tasks.add_task') }}</x-ui.button>@endcan
                    </x-slot:action>
                </x-ui.empty-state>
            @else
                @php
                    $taskDone = $this->tasks->where('status', TaskStatus::Done)->count();
                    $taskTotal = $this->tasks->count();
                    $taskOverdue = $this->tasks->filter(fn (Task $task): bool => $task->status !== TaskStatus::Done && $task->due_date !== null && $task->due_date->lt(today()))->count();
                    $taskUnassigned = $this->tasks->filter(fn (Task $task): bool => $task->taskAssignees->isEmpty())->count();
                    $taskInkText = 'text-secondary-dark dark:text-ink';
                    $taskMutedText = 'text-secondary-medium dark:text-ink-muted';

                    $ordered = $this->tasks->sortBy([
                        fn (Task $a, Task $b): int => ($a->status === TaskStatus::Done) <=> ($b->status === TaskStatus::Done),
                        fn (Task $a, Task $b): int => ($a->due_date === null) <=> ($b->due_date === null),
                        fn (Task $a, Task $b): int => ($a->due_date?->getTimestamp() ?? 0) <=> ($b->due_date?->getTimestamp() ?? 0),
                        fn (Task $a, Task $b): int => $a->id <=> $b->id,
                    ]);
                    $taskGroups = $this->allProjects
                        ->map(fn (Project $project): array => [
                            'project' => $project,
                            'tasks' => $ordered->filter(fn (Task $task): bool => $this->taskProjectId($task) === $project->id)->values(),
                        ])
                        ->filter(fn (array $group): bool => $group['tasks']->isNotEmpty())
                        ->values();
                    $looseTasks = $ordered->filter(fn (Task $task): bool => $this->taskProjectId($task) === null)->values();
                @endphp

                <div class="grid gap-3 border-b border-secondary-soft p-3.5 @lg/detail:grid-cols-3 dark:border-line">
                    <x-ui.stat-tile :label="__('programs.stat_tasks_done')" :value="$taskDone" :denominator="$taskTotal" />
                    <x-ui.stat-tile :label="__('tasks.overdue')" :value="$taskOverdue" :tone="$taskOverdue > 0 ? 'warn' : null" />
                    <x-ui.stat-tile :label="__('common.unassigned')" :value="$taskUnassigned" />
                </div>

                <div class="hidden grid-cols-[1.25rem_minmax(14rem,1fr)_9rem_5.5rem_7rem_6.5rem_7rem] gap-x-4 px-3.5 pt-4 @5xl/detail:grid" aria-hidden="true">
                    <span></span>
                    @foreach ([__('tasks.task'), __('tasks.assignee'), __('tasks.priority'), __('tasks.due'), __('tasks.progress'), __('common.status')] as $column)
                        <span @class(['type-label', $taskMutedText, 'text-end' => $loop->last])>{{ $column }}</span>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 px-3.5 pt-3">
                    <x-ui.button tone="ghost" size="sm" x-on:click="$dispatch('program-task-groups', true)" data-test="expand-task-groups">{{ __('programs.expand_all') }}</x-ui.button>
                    <x-ui.button tone="ghost" size="sm" x-on:click="$dispatch('program-task-groups', false)" data-test="collapse-task-groups">{{ __('programs.collapse_all') }}</x-ui.button>
                </div>

                <div class="flex flex-col gap-2 px-3.5 pb-3.5 @6xl/detail:px-5 @6xl/detail:pb-5">
                    @foreach ($taskGroups as $group)
                        @php
                            $groupPastDue = $group['tasks']->filter(fn (Task $task): bool => $task->status !== TaskStatus::Done && $task->due_date !== null && $task->due_date->lt(today()))->count();
                            $groupOpen = $group['tasks']->where('status', '!=', TaskStatus::Done)->count();
                        @endphp
                        <section class="min-w-0" wire:key="program-task-group-{{ $group['project']->id }}" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" @program-task-groups.window="open = $event.detail" data-task-group>
                            <header class="flex items-center gap-3 border-b border-secondary-soft pt-4 pb-3 dark:border-line">
                                <button type="button" class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 text-start focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary-medium" @click="open = ! open" :aria-expanded="open.toString()">
                                    <span class="inline-flex shrink-0 transition-transform [&_svg]:size-4" :class="open ? 'rotate-90' : 'rtl:rotate-180'" aria-hidden="true"><x-desk.icon name="chev" /></span>
                                    <span class="min-w-0 truncate text-base font-bold {{ $taskInkText }}">{{ $group['project']->name }}</span>
                                    <span class="shrink-0 text-xs font-semibold {{ $taskMutedText }}">{{ trans_choice('programs.task_group_open', $groupOpen, ['count' => $groupOpen]) }}</span>
                                    @if ($groupPastDue > 0)
                                        <span class="shrink-0 rounded-full bg-status-critical-soft px-2 py-0.5 text-xs font-bold text-status-critical">{{ trans_choice('programs.ledger_past_due', $groupPastDue, ['count' => $groupPastDue]) }}</span>
                                    @endif
                                </button>
                                <x-ui.button tone="ghost" size="sm" icon="eye" :href="route('projects.show', $group['project'])" wire:navigate :aria-label="__('common.view_name', ['name' => $group['project']->name])" />
                            </header>
                            <ul class="divide-y divide-secondary-soft dark:divide-line" x-show="open" x-collapse @if (! $loop->first) x-cloak @endif>
                                @foreach ($group['tasks'] as $task)
                                    @include('pages.programs._task-row', ['task' => $task, 'inkText' => $taskInkText, 'mutedText' => $taskMutedText])
                                @endforeach
                            </ul>
                        </section>
                    @endforeach

                    @if ($looseTasks->isNotEmpty())
                        <section class="min-w-0" data-loose-tasks>
                            <header class="border-b border-secondary-soft pt-4 pb-3 dark:border-line">
                                <p class="text-base font-bold {{ $taskInkText }}">{{ __('programs.work_not_tied_to_a_project') }}</p>
                            </header>
                            <ul class="divide-y divide-secondary-soft dark:divide-line">
                                @foreach ($looseTasks as $task)
                                    @include('pages.programs._task-row', ['task' => $task, 'inkText' => $taskInkText, 'mutedText' => $taskMutedText])
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            @endif
        </x-ui.panel>
