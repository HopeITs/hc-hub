<?php

use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Tracking\Actions\Tasks\CreateTask;
use Modules\Tracking\Livewire\Concerns\AddsQuickTask;
use Modules\Tracking\Models\Task;

/**
 * The one Tasks section any record can embed: lists the top-level tasks raised
 * on it and adds more through the shared quick-add dialog. The record is a
 * morph map key plus an id (`employee`, `department`, `program`...), the same
 * source a task carries. What the viewer sees is decided by TaskVisibilityScope.
 */
new class extends Component {
    use AddsQuickTask;

    #[Locked]
    public string $subjectType = '';

    #[Locked]
    public int $subjectId = 0;

    public function mount(string $subjectType, int $subjectId): void
    {
        abort_unless(in_array($subjectType, CreateTask::SOURCE_TYPES, true), 404);

        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;

        $this->authorize('viewAny', Task::class);
    }

    /** @return Collection<int, Task> */
    #[Computed]
    public function subjectTasks(): Collection
    {
        return Task::query()
            ->select(['id', 'slug', 'title', 'status', 'priority', 'due_date'])
            ->forSubject($this->subjectType, $this->subjectId)
            ->whereNull('parent_task_id')
            ->with('taskAssignees.employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar')
            ->orderBy('id')
            ->get();
    }

    /** @return array{type: string, id: int} */
    protected function quickTaskSource(): array
    {
        return ['type' => $this->subjectType, 'id' => $this->subjectId];
    }

    protected function quickTaskCreated(): void
    {
        unset($this->subjectTasks);
    }
};
?>

<div data-test="subject-tasks">
    <x-ui.panel :title="__('tasks.subject_tasks_title')" :count="$this->subjectTasks->count()">
        <x-slot:toolbar>
            @can('create', Modules\Tracking\Models\Task::class)<x-ui.button tone="primary" size="sm" icon="plus" wire:click="openAddTaskModal" data-test="add-subject-task-button">{{ __('tasks.add_task') }}</x-ui.button>@endcan
        </x-slot:toolbar>

        @if ($this->subjectTasks->isEmpty())
            <x-ui.empty-state icon="clipboard-document-list" :heading="__('tasks.subject_tasks_empty')" />
        @else
            <ul class="divide-y divide-secondary-soft dark:divide-line">
                @foreach ($this->subjectTasks as $subjectTask)
                    @php $assignees = $subjectTask->taskAssignees->pluck('employee')->filter(); @endphp
                    <li class="flex flex-wrap items-center justify-between gap-3 p-3.5" wire:key="subject-task-{{ $subjectTask->id }}">
                        <a href="{{ route('tasks.show', $subjectTask) }}" wire:navigate class="min-w-0 flex-1 truncate text-sm font-semibold hover:underline">
                            {{ $subjectTask->title }}
                        </a>
                        <div class="flex shrink-0 items-center gap-3">
                            <span class="w-40 shrink-0 truncate text-xs text-secondary-medium dark:text-ink-muted">
                                {{ $assignees->isEmpty() ? __('common.not_assigned') : $assignees->map->name->implode(', ') }}
                            </span>
                            <span class="text-xs tabular-nums text-secondary-medium dark:text-ink-muted">
                                {{ $subjectTask->due_date?->translatedFormat(__('formats.date')) ?? '—' }}
                            </span>
                            <x-ui.enum-badge :case="$subjectTask->status" />
                            <x-ui.enum-badge :case="$subjectTask->priority" />
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.panel>

    <x-tracking::tasks.add-task-modal :open="$showAddTaskModal" test-prefix="new-subject-task" />
</div>
