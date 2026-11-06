{{-- The properties column on ⚡show.blade.php: status, priority, assignees, dates, progress and what the task is related to. Each field saves the moment it changes for whoever may change it (updated() in the page); everyone else reads it as text. Only the creator moves the due date. --}}
@use('App\Enums\TaskPriority')
@php
    $propertyLabelClass = 'text-[11px] font-semibold tracking-[0.1em] text-secondary-medium uppercase dark:text-ink-muted';
    $chipClass = 'inline-flex min-h-10 cursor-pointer touch-manipulation items-center justify-center gap-1.5 rounded-lg border px-2.5 py-2 text-xs font-semibold whitespace-nowrap transition-colors';
    $chipSelectedClass = 'border-secondary-dark bg-secondary-dark text-white dark:border-ink dark:bg-ink dark:text-surface-0';
    $chipIdleClass = 'border-secondary-light text-secondary-medium hover:border-secondary-medium hover:text-secondary-dark dark:border-line-strong dark:text-ink-muted dark:hover:text-ink';
    $fieldClass = 'min-h-10 w-full rounded-lg border border-secondary-light bg-white px-2.5 py-1.5 text-sm text-secondary-dark focus:ring-1 focus:ring-secondary-light focus:outline-none dark:border-field-line dark:bg-surface-2 dark:text-ink dark:[color-scheme:dark]';
    $readValueClass = 'text-sm font-medium tabular-nums text-secondary-dark dark:text-ink';
    $assigneeNames = $this->task->taskAssignees->pluck('employee')->filter()->map->name;
    $dateFields = [
        ['property' => 'startDate', 'label' => __('common.start_date'), 'value' => $this->task->start_date, 'editable' => $canEditTask, 'test' => 'task-edit-start-date-input'],
        ['property' => 'dueDate', 'label' => __('tasks.due_date_2'), 'value' => $this->task->due_date, 'editable' => $canRescheduleTask, 'test' => 'task-edit-due-date-input'],
        ['property' => 'completedDate', 'label' => __('tasks.completed_date'), 'value' => $this->task->completed_date, 'editable' => $canUpdateTaskStatus, 'test' => 'task-edit-completed-date-input'],
    ];
    $sourceTypeLabel = match ($this->task->source_type) {
        'milestone' => __('tasks.source_type_milestone'),
        'proposal' => __('tasks.source_type_proposal'),
        'project' => __('tasks.source_type_project'),
        'program' => __('tasks.source_type_program'),
        'department' => __('tasks.source_type_department'),
        default => __('tasks.source'),
    };
    $source = $this->task->source;
    $sourceUrl = $source instanceof \App\Models\Milestone && auth()->user()->can('view', $source) ? route('milestones.show', $source) : null;
@endphp

<x-ui.panel :clip="false">
    <h2 id="task-properties-heading" class="border-b border-panel-border px-4 py-3 text-base font-bold text-secondary-dark dark:text-ink">{{ __('tasks.properties') }}</h2>

    <div class="divide-y divide-panel-border" @if ($canUpdateTaskStatus) data-test="task-edit-form" @endif>
        @include('pages.tasks._show-status-panel')

        <div class="grid gap-1.5 px-4 py-3">
            <p class="{{ $propertyLabelClass }}" id="task-edit-priority-label">{{ __('tasks.priority') }}</p>
            @if ($canEditTask)
                <div class="grid grid-cols-3 gap-1.5" role="radiogroup" aria-labelledby="task-edit-priority-label">
                    @foreach (TaskPriority::cases() as $priorityCase)
                        @php
                            $isPrioritySelected = $taskPriority === $priorityCase->value;
                        @endphp
                        <button
                            type="button"
                            role="radio"
                            aria-checked="{{ $isPrioritySelected ? 'true' : 'false' }}"
                            wire:click="$set('taskPriority', '{{ $priorityCase->value }}')"
                            @class([$chipClass, $chipSelectedClass => $isPrioritySelected, $chipIdleClass => ! $isPrioritySelected])
                            data-test="task-edit-priority-{{ $priorityCase->value }}"
                        >
                            {{ $priorityCase->label() }}
                        </button>
                    @endforeach
                </div>
                <flux:error name="taskPriority" />
            @else
                <div><x-ui.enum-badge :case="$this->task->priority" /></div>
            @endif
        </div>

        <div class="grid gap-1.5 px-4 py-3">
            @if ($canEditTask)
                <x-ui.form-multiselect wire:model.live="assignedTo" :label="__('tasks.assignees')" :placeholder="__('common.select')" data-test="task-edit-assignee-picker" employees />
            @else
                <p class="{{ $propertyLabelClass }}">{{ __('tasks.assignees') }}</p>
                @if ($assigneeNames->isEmpty())
                    <p class="text-sm text-secondary-medium dark:text-ink-muted">{{ __('common.not_assigned') }}</p>
                @else
                    <ul class="grid gap-1" data-test="task-assignee-names">
                        @foreach ($assigneeNames as $assigneeName)
                            <li class="{{ $readValueClass }}">{{ $assigneeName }}</li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </div>

        <div class="grid gap-3 px-4 py-3">
            @foreach ($dateFields as $dateField)
                <div class="grid gap-1.5">
                    @if ($dateField['editable'])
                        <label class="{{ $propertyLabelClass }}" for="task-property-{{ $dateField['property'] }}">{{ $dateField['label'] }}</label>
                        <input id="task-property-{{ $dateField['property'] }}" type="date" wire:model.live.change="{{ $dateField['property'] }}" class="{{ $fieldClass }}" data-test="{{ $dateField['test'] }}" />
                        <flux:error :name="$dateField['property']" />
                    @else
                        <p class="{{ $propertyLabelClass }}">{{ $dateField['label'] }}</p>
                        <p class="{{ $readValueClass }}" data-test="{{ $dateField['test'] }}-text">{{ $dateField['value']?->translatedFormat(__('formats.date')) ?? '—' }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="grid gap-1.5 px-4 py-3">
            <div class="flex items-center justify-between gap-2">
                @if ($canUpdateTaskStatus)
                    <label class="{{ $propertyLabelClass }}" for="task-edit-progress-slider">{{ __('tasks.progress') }}</label>
                @else
                    <p class="{{ $propertyLabelClass }}">{{ __('tasks.progress') }}</p>
                @endif
                <span class="{{ $readValueClass }}" data-test="task-progress-value">{{ $this->task->progress_percentage }}%</span>
            </div>
            @if ($canUpdateTaskStatus)
                <div class="flex items-center gap-3">
                    <input id="task-edit-progress-slider" type="range" wire:model.live.change="progressPercentage" min="0" max="100" step="1" class="h-1.5 w-full flex-1 cursor-pointer accent-primary" data-test="task-edit-progress-slider" />
                    <div class="relative w-16 shrink-0">
                        <input type="number" wire:model.live.change="progressPercentage" min="0" max="100" required class="w-full rounded-lg border border-secondary-light bg-white py-1.5 ps-2 pe-6 text-end text-sm font-semibold tabular-nums text-secondary-dark focus:ring-1 focus:ring-secondary-light focus:outline-none dark:border-field-line dark:bg-surface-2 dark:text-ink" aria-label="{{ __('tasks.progress_2') }}" data-test="task-edit-progress-input" />
                        <span class="pointer-events-none absolute inset-y-0 end-2 flex items-center text-sm text-secondary-medium dark:text-ink-muted">%</span>
                    </div>
                </div>
                <flux:error name="progressPercentage" />
            @else
                <div class="h-1.5 overflow-hidden rounded-full bg-secondary-soft dark:bg-surface-2" aria-hidden="true">
                    <div class="h-full rounded-full bg-primary" style="width: {{ $this->task->progress_percentage }}%"></div>
                </div>
            @endif
        </div>

        @if ($canEditTask)
            <div class="px-4 py-3">
                <flux:checkbox wire:model.live="evidenceRequired" :label="__('tasks.workflow.evidence_required')" :description="__('tasks.workflow.evidence_required_help')" data-test="task-evidence-required" />
            </div>
        @elseif ($this->task->evidence_required)
            <div class="grid gap-1 px-4 py-3">
                <p class="{{ $readValueClass }}">{{ __('tasks.workflow.evidence_required') }}</p>
                <p class="text-xs text-secondary-medium dark:text-ink-muted">{{ __('tasks.workflow.evidence_required_help') }}</p>
            </div>
        @endif

        <div class="grid gap-1 px-4 py-3">
            <p class="{{ $propertyLabelClass }}">{{ __('tasks.source_type') }}</p>
            <p class="text-xs text-secondary-medium dark:text-ink-muted">{{ $sourceTypeLabel }}</p>
            @if ($sourceUrl !== null)
                <a href="{{ $sourceUrl }}" wire:navigate class="{{ $readValueClass }} hover:underline" data-test="task-related-to">{{ $this->task->sourceLabel() }}</a>
            @else
                <p class="{{ $readValueClass }}" data-test="task-related-to">{{ $this->task->sourceLabel() }}</p>
            @endif
        </div>
    </div>
</x-ui.panel>
