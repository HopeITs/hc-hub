@use('Modules\Tracking\Enums\TaskPriority')
@use('Modules\Tracking\Enums\TaskStatus')
@use('App\Support\FluxMarkup')
@use('Illuminate\Support\Js')
                @foreach ($columnTasks as $task)
                    @php
                        $isDone = $task->status === TaskStatus::Done;
                        $isMissed = $task->isMissed();
                        $isOverdue = $task->isOverdue();
                        // What the viewer may do to this card (TaskPolicy).
                        $canUpdate = auth()->user()->can('update', $task);
                        $canMove = $canUpdate || auth()->user()->can('updateStatus', $task);
                        $canDelete = auth()->user()->can('delete', $task);
                        $canCreate = $canCreateTask;
                        $isDueDateLocked = ! $canUpdate || $task->created_by !== auth()->id();
                        $assignedIds = $task->taskAssignees->pluck('employee_id')->all();
                        $boardAssignees = $task->taskAssignees->pluck('employee')->filter();
                    @endphp
                    <div
                        @class([
                            'group relative flex flex-col gap-2 rounded-xl border border-secondary-soft bg-white p-3 shadow-hairline transition-shadow duration-150 hover:shadow-md dark:border-line dark:bg-surface-1',
                            'cursor-grab active:cursor-grabbing' => ! $isDone && $canMove,
                        ])
                        wire:key="task-board-card-{{ $task->id }}"
                        data-task-id="{{ $task->id }}"
                        draggable="{{ $isDone || ! $canMove ? 'false' : 'true' }}"
                        x-on:dragstart="$event.dataTransfer.setData('text/plain', JSON.stringify({ id: {{ $task->id }}, status: '{{ $case->value }}' })); $event.currentTarget.classList.add('opacity-40')"
                        x-on:dragend="$event.currentTarget.classList.remove('opacity-40')"
                    >
                        {{-- Actions float over the card's top corner on hover/focus, so the title keeps the full width. --}}
                        @if (($canMove && ! $isDone) || $canCreate || $canUpdate || $canDelete)
                            <div class="absolute -top-3 end-2 z-10 flex items-center gap-0.5 rounded-lg border border-secondary-soft bg-white p-0.5 opacity-0 shadow-sm transition-opacity focus-within:opacity-100 group-hover:opacity-100 dark:border-line dark:bg-surface-2">
                                @if ($canMove && ! $isDone)
                                    {{-- Keyboard alternative to dragging: the shared status menu (_task-option-menu). --}}
                                    <button
                                        type="button"
                                        x-data
                                        aria-haspopup="menu"
                                        x-on:click="$store.taskMenu.openFor('status', 'board-status-{{ $task->id }}', '{{ $task->status->value }}', $el.getBoundingClientRect(), 'quickSetStatus', [{{ $task->id }}])"
                                        aria-label="{{ __('tasks.change_status') }}: {{ $task->title }}"
                                        title="{{ __('tasks.change_status') }}"
                                        class="{{ FluxMarkup::GhostIconButton }} size-7!"
                                        data-test="task-quick-status-{{ $task->id }}"
                                    >
                                        <flux:icon name="arrows-right-left" variant="mini" class="size-4" aria-hidden="true" />
                                    </button>
                                @endif
                                @if ($canCreate)
                                    {{ FluxMarkup::ghostIconButton('plus', __('tasks.add_subtask'), 'title="'.e(__('tasks.add_subtask')).'" x-on:click="subtasksOpen['.$task->id.'] = true; $nextTick(() => $refs[\'subtaskAddInputBoard'.$task->id.'\']?.focus())" data-test="task-board-add-subtask-'.$task->id.'"', 'size-7!') }}
                                @endif
                                @if ($canUpdate)
                                    {{ FluxMarkup::ghostIconButton('pencil-square', __('tasks.edit_title', ['title' => $task->title]), 'title="'.e(__('tasks.edit_task')).'" wire:click="openEditDrawer('.$task->id.')"', 'size-7!') }}
                                @endif
                                @if ($canDelete)
                                    {{ FluxMarkup::ghostIconButton('trash', __('tasks.delete_title', ['title' => $task->title]), 'title="'.e(__('tasks.delete_task')).'" wire:click="confirmDelete('.$task->id.')"', 'size-7!') }}
                                @endif
                            </div>
                        @endif

                        <div class="flex min-w-0 items-start gap-1.5">
                            @if (in_array($task->id, $this->unreadIds, true))
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-primary dark:bg-ink" data-unread-dot="task-{{ $task->id }}" title="{{ __('notifications.has_unread') }}"><span class="sr-only">{{ __('notifications.has_unread') }}</span></span>
                            @endif
                            @if ($task->isReportTask())
                                <x-ui.enum-badge :case="$task->kind" data-test="report-task-chip" />
                            @endif
                            <a href="{{ $task->reportUrl() ?? route('tasks.show', $task) }}" wire:navigate dir="auto" title="{{ $task->title }}" class="min-w-0 line-clamp-2 text-start text-sm font-semibold break-words text-secondary-dark hover:text-primary hover:underline dark:text-ink dark:hover:text-status-critical" data-test="task-board-card-{{ $task->id }}">
                                {{ $task->title }}
                            </a>
                        </div>

                        <p dir="auto" class="-mt-1 truncate text-start text-xs text-secondary-medium dark:text-ink-muted" title="{{ $task->sourceLabel() }}">{{ $task->sourceLabel() }}</p>

                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            {{-- Assignees: a click opens the shared picker (editors only); hovering a face shows who it is (_person-card). --}}
                            <div
                                x-data
                                role="button"
                                @if ($canUpdate)
                                tabindex="0"
                                x-on:click="$store.assigneePicker.openFor({{ $task->id }}, {{ Js::from($assignedIds) }}, $el.getBoundingClientRect())"
                                x-on:keydown.enter="$store.assigneePicker.openFor({{ $task->id }}, {{ Js::from($assignedIds) }}, $el.getBoundingClientRect())"
                                x-on:keydown.space.prevent="$store.assigneePicker.openFor({{ $task->id }}, {{ Js::from($assignedIds) }}, $el.getBoundingClientRect())"
                                @endif
                                aria-haspopup="listbox"
                                x-bind:aria-expanded="$store.assigneePicker.open && $store.assigneePicker.taskId === {{ $task->id }} ? 'true' : 'false'"
                                aria-label="{{ __('tasks.assignees') }}: {{ $boardAssignees->isEmpty() ? __('common.not_assigned') : $boardAssignees->map->name->implode(', ') }}"
                                @class(['inline-flex items-center -space-x-1.5 rtl:space-x-reverse', 'cursor-pointer' => $canUpdate])
                                data-test="task-board-assignee-trigger-{{ $task->id }}"
                            >
                                @if ($boardAssignees->isEmpty())
                                    <span class="inline-flex size-6 items-center justify-center rounded-full border border-dashed border-secondary-light text-secondary-light">
                                        {{ FluxMarkup::icon('user-plus', 'micro', 'size-3') }}
                                    </span>
                                @else
                                    @foreach ($boardAssignees->take(3) as $employee)
                                        @php $employeePhoto = Modules\Hr\Models\Employee::avatarUrl($employee->id); @endphp
                                        <img
                                            src="{{ $employeePhoto }}"
                                            alt=""
                                            loading="lazy"
                                            decoding="async"
                                            onerror="this.onerror = null; this.src = '{{ asset(Modules\Hr\Models\Employee::DefaultAvatar) }}'"
                                            class="size-6 rounded-full !bg-hr-avatar object-cover ring-2 ring-white dark:ring-surface-1"
                                            x-on:mouseenter="$store.personCard.show($el)"
                                            x-on:mouseleave="$store.personCard.hide()"
                                            data-person-name="{{ $employee->name }}"
                                            data-person-alt-name="{{ $employee->secondary_name }}"
                                            data-person-role="{{ Modules\Hr\Models\Employee::positionSummary($employee->id) }}"
                                            data-person-photo="{{ $employeePhoto }}"
                                            data-test="task-board-assignee-{{ $task->id }}-{{ $employee->id }}"
                                        />
                                    @endforeach
                                    @if ($boardAssignees->count() > 3)
                                        <span title="{{ $boardAssignees->skip(3)->map->name->implode(', ') }}" class="inline-flex size-6 items-center justify-center rounded-full bg-secondary-soft text-[10px] font-semibold text-secondary-dark ring-2 ring-white dark:bg-surface-3 dark:text-ink dark:ring-surface-1">
                                            +{{ $boardAssignees->count() - 3 }}
                                        </span>
                                    @endif
                                @endif
                            </div>

                            @if (! $canUpdate)
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 font-semibold {{ $priorityChipClasses($task->priority) }}" data-test="task-board-priority-{{ $task->id }}">
                                    {{ FluxMarkup::icon('flag', $task->priority === TaskPriority::Low ? 'outline' : 'solid', 'size-3 shrink-0') }}
                                    {{ $task->priority->label() }}
                                </span>
                            @else
                                <button type="button" x-data aria-haspopup="menu" x-on:click="$store.taskMenu.openFor('priority', 'board-priority-{{ $task->id }}', '{{ $task->priority->value }}', $el.getBoundingClientRect(), 'quickSetPriority', [{{ $task->id }}])" aria-label="{{ __('tasks.priority') }}: {{ $task->priority->label() }}" class="inline-flex cursor-pointer items-center gap-1 rounded-full px-2 py-1 font-semibold {{ $priorityChipClasses($task->priority) }}" data-test="task-board-priority-trigger-{{ $task->id }}">
                                    {{ FluxMarkup::icon('flag', $task->priority === TaskPriority::Low ? 'outline' : 'solid', 'size-3 shrink-0') }}
                                    {{ $task->priority->label() }}
                                </button>
                            @endif

                            @if ($isMissed)
                                <span class="inline-flex items-center gap-1 rounded-full bg-status-critical-soft px-2 py-1 font-semibold text-primary dark:text-status-critical" data-test="task-board-missed-{{ $task->id }}">
                                    {{ FluxMarkup::icon('exclamation-triangle', 'micro', 'size-3 shrink-0') }}
                                    {{ __('tasks.missed') }}
                                </span>
                            @endif

                            <div @class(['inline-flex items-center gap-1 rounded-full px-2 py-1 font-semibold', 'bg-status-critical-soft text-primary dark:text-status-critical' => $isOverdue, 'bg-secondary-soft text-secondary-medium dark:bg-white/10 dark:text-ink-muted' => ! $isOverdue])>
                                {{ FluxMarkup::icon('calendar-days', 'micro', 'size-3 shrink-0') }}
                                @if ($isDueDateLocked)
                                    {{-- Plain text: a greyed-out date box reads as broken to someone who may only look. --}}
                                    <span
                                        class="tabular-nums"
                                        @if ($canUpdate) title="{{ __('tasks.only_the_task_creator_can_change_the_due_date') }}" @endif
                                        data-test="task-board-due-date-text-{{ $task->id }}"
                                    >{{ $task->due_date?->translatedFormat(__($task->due_date->isCurrentYear() ? 'formats.date_short' : 'formats.date')) ?? __('tasks.no_due_date') }}</span>
                                @else
                                    <input
                                        type="date"
                                        value="{{ $task->due_date?->toDateString() }}"
                                        wire:change="rescheduleTask({{ $task->id }}, $event.target.value)"
                                        aria-label="{{ __('tasks.due_date_2') }}: {{ $task->title }}"
                                        class="w-24 border-0 bg-transparent p-0 tabular-nums focus:ring-0 focus:outline-none"
                                        data-test="task-board-due-date-{{ $task->id }}"
                                    />
                                @endif
                            </div>

                            @if ($task->subtasks_count > 0)
                                <button
                                    type="button"
                                    x-on:click="subtasksOpen[{{ $task->id }}] = !subtasksOpen[{{ $task->id }}]"
                                    :aria-expanded="!!subtasksOpen[{{ $task->id }}]"
                                    class="inline-flex items-center gap-1 rounded-full bg-secondary-soft px-2 py-1 font-semibold text-secondary-medium hover:bg-secondary-light/60 dark:bg-white/10 dark:text-ink-muted dark:hover:bg-surface-2"
                                    :aria-label="subtasksOpen[{{ $task->id }}] ? {{ Js::from(__('tasks.hide_subtasks')) }} : {{ Js::from(__('tasks.show_subtasks')) }}"
                                    data-test="task-board-subtasks-toggle-{{ $task->id }}"
                                >
                                    {{ FluxMarkup::icon('rectangle-stack', 'micro', 'size-3 shrink-0') }}
                                    {{ trans_choice('tasks.subtask_count', $task->subtasks_count, ['count' => $task->subtasks_count]) }}
                                </button>
                            @endif
                        </div>

                        <div x-show="subtasksOpen[{{ $task->id }}]" x-cloak x-collapse class="flex flex-col gap-1.5 rounded-lg bg-secondary-soft/30 p-2 dark:bg-white/[0.04]">
                            @foreach ($task->subtasks as $subtask)
                                @php $boardSubtaskAssignees = $subtask->taskAssignees->pluck('employee')->filter(); @endphp
                                <div class="flex flex-wrap items-center gap-1.5" wire:key="task-board-subtask-row-{{ $subtask->id }}" data-test="task-board-subtask-row-{{ $subtask->id }}">
                                    <button
                                        type="button"
                                        wire:click="toggleComplete({{ $subtask->id }})"
                                        @disabled($subtask->status === TaskStatus::Done || ! auth()->user()->can('updateStatus', $subtask))
                                        aria-label="{{ __('tasks.mark_complete') }}"
                                        class="inline-flex size-5 shrink-0 cursor-pointer items-center justify-center rounded-full text-secondary-medium hover:bg-secondary-soft disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:bg-transparent dark:text-ink-muted dark:hover:bg-surface-2"
                                    >
                                        {{ FluxMarkup::icon('check-circle', $subtask->status === TaskStatus::Done ? 'solid' : 'outline', 'size-4 '.($subtask->status === TaskStatus::Done ? 'text-status-good' : '')) }}
                                    </button>
                                    <a
                                        href="{{ route('tasks.show', $subtask) }}"
                                        wire:navigate
                                        title="{{ $subtask->title }}"
                                        @class(['min-w-0 flex-1 truncate text-xs hover:underline', 'text-secondary-light line-through' => $subtask->status === TaskStatus::Done, 'text-secondary-dark dark:text-ink' => $subtask->status !== TaskStatus::Done])
                                    >{{ $subtask->title }}</a>
                                    @if (! auth()->user()->can('update', $subtask))
                                        {{ FluxMarkup::icon('flag', $subtask->priority === TaskPriority::Low ? 'outline' : 'solid', 'size-3 shrink-0 '.$priorityIconClasses($subtask->priority)) }}
                                    @else
                                    <button type="button" x-data aria-haspopup="menu" x-on:click="$store.taskMenu.openFor('priority', 'board-sub-priority-{{ $subtask->id }}', '{{ $subtask->priority->value }}', $el.getBoundingClientRect(), 'quickSetPriority', [{{ $subtask->id }}])" aria-label="{{ __('tasks.priority') }}: {{ $subtask->priority->label() }}" class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded hover:bg-secondary-soft dark:hover:bg-surface-2" data-test="task-board-subtask-priority-trigger-{{ $subtask->id }}">
                                {{ FluxMarkup::icon('flag', $subtask->priority === TaskPriority::Low ? 'outline' : 'solid', 'size-3 shrink-0 '.$priorityIconClasses($subtask->priority)) }}
                            </button>
                                    @endif
                                    {{ FluxMarkup::statusPill($subtask->status, 'shrink-0') }}
                                    <div class="basis-full ps-7 text-[11px] text-secondary-medium dark:text-ink-muted">
                                        {{ $boardSubtaskAssignees->isEmpty() ? __('common.not_assigned') : $boardSubtaskAssignees->map->name->implode(', ') }}
                                        @if ($subtask->due_date !== null)
                                            · {{ $subtask->due_date->translatedFormat(__('formats.date_short')) }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            @if ($canCreate)
                            @php $boardSubtaskDraft = $this->subtaskDraft($task); @endphp
                            <div class="flex flex-col gap-1.5 pt-0.5" data-test="task-board-subtask-add-row-{{ $task->id }}">
                                <label class="flex items-center gap-2">
                                    {{ FluxMarkup::icon('plus', 'micro', 'size-3.5 shrink-0 text-primary dark:text-brand-text') }}
                                    <span class="sr-only">{{ __('tasks.add_subtask') }}</span>
                                    <input
                                        type="text"
                                        x-ref="subtaskAddInputBoard{{ $task->id }}"
                                        placeholder="{{ __('tasks.add_subtask') }}"
                                        class="w-full min-w-0 border-0 bg-transparent p-1 text-xs text-secondary-dark placeholder:text-secondary-light focus:ring-0 focus:outline-none dark:text-ink"
                                        wire:keydown.enter="addSubtaskQuick({{ $task->id }}, $event.target.value); $event.target.value = ''"
                                        data-test="task-board-subtask-quick-add-{{ $task->id }}"
                                    />
                                </label>
                                <div class="flex flex-wrap items-center gap-1.5 ps-5">
                                    @php
                                        $boardDraftIds = collect($boardSubtaskDraft['assignees'])->map(fn ($id) => (int) $id)->values()->all();
                                        $boardDraftAssignees = collect($this->userOptions)->whereIn('id', $boardDraftIds);
                                        $boardDraftPicker = "\$store.assigneePicker.openFor('board-subtask-{$task->id}', ".Js::from($boardDraftIds).", \$el.getBoundingClientRect(), 'toggleSubtaskDraftAssignee', [{$task->id}])";
                                    @endphp
                                    <div
                                        x-data
                                        role="button"
                                        tabindex="0"
                                        x-on:click="{{ $boardDraftPicker }}"
                                        x-on:keydown.enter="{{ $boardDraftPicker }}"
                                        x-on:keydown.space.prevent="{{ $boardDraftPicker }}"
                                        aria-haspopup="listbox"
                                        aria-label="{{ __('tasks.assignees') }}"
                                        class="inline-flex shrink-0 cursor-pointer items-center -space-x-2 rtl:space-x-reverse"
                                        data-test="task-board-subtask-assignee-trigger-{{ $task->id }}"
                                    >
                                            @if ($boardDraftAssignees->isEmpty())
                                                <span class="inline-flex size-6 items-center justify-center rounded-full border border-dashed border-secondary-light text-secondary-light">
                                                    {{ FluxMarkup::icon('user-plus', 'micro', 'size-3') }}
                                                </span>
                                            @else
                                                @foreach ($boardDraftAssignees->take(3) as $userOption)
                                                    <span class="inline-flex size-6 items-center justify-center rounded-full bg-secondary-dark text-[10px] font-semibold text-white ring-2 ring-white dark:bg-surface-3 dark:ring-surface-1">
                                                        {{ Illuminate\Support\Str::of($userOption['name'])->explode(' ')->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}
                                                    </span>
                                                @endforeach
                                                @if ($boardDraftAssignees->count() > 3)
                                                    <span class="inline-flex size-6 items-center justify-center rounded-full bg-secondary-soft text-[9px] font-semibold text-secondary-dark ring-2 ring-white dark:bg-surface-3 dark:text-ink dark:ring-surface-1">
                                                        +{{ $boardDraftAssignees->count() - 3 }}
                                                    </span>
                                                @endif
                                            @endif
                                    </div>

                                    <input
                                        type="date"
                                        wire:model="subtaskDrafts.{{ $task->id }}.due_date"
                                        value="{{ $boardSubtaskDraft['due_date'] }}"
                                        aria-label="{{ __('tasks.due_date_2') }}"
                                        class="w-24 shrink-0 rounded border-0 bg-transparent p-0.5 text-[11px] tabular-nums text-secondary-medium focus:ring-1 focus:ring-secondary-light focus:outline-none dark:text-ink-muted"
                                        data-test="task-board-subtask-due-date-{{ $task->id }}"
                                    />

                                    <select
                                        wire:model="subtaskDrafts.{{ $task->id }}.priority"
                                        aria-label="{{ __('tasks.priority') }}"
                                        class="shrink-0 rounded-md border border-secondary-soft bg-white px-1 py-0.5 text-[11px] text-secondary-dark dark:border-line dark:bg-surface-1 dark:text-ink"
                                        data-test="task-board-subtask-priority-{{ $task->id }}"
                                    >
                                        @foreach (TaskPriority::cases() as $priorityCase)
                                            <option value="{{ $priorityCase->value }}" @selected($boardSubtaskDraft['priority'] === $priorityCase->value)>{{ $priorityCase->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach
