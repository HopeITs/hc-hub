@props([
    'testPrefix',
    'open' => true,
])

{{--
    The quick add-task form shared by the milestone, program, project and
    proposal pages. Each page's component owns the same properties
    (newTaskTitle, newTaskStatus, newTaskPriority, newTaskAssignedTo,
    newTaskDueDate, newTaskTarget) and actions (addTask, closeAddTaskModal);
    `testPrefix` keeps each page's data-test hooks distinct. "Goes to" shows
    when the page's record sits in a program (AddsQuickTask::quickTaskPlaces).
--}}
@php
    $places = $open ? $__env->shared('__livewire')?->quickTaskPlaces : null;
@endphp
<x-ui.modal
    wire:model="showAddTaskModal"
    size="md"
    :title="__('tasks.add_task')"
    submit="addTask"
    close="closeAddTaskModal"
    required-note
>
    @if ($open)
        <div class="grid grid-cols-2 gap-x-4 gap-y-4">
            <x-ui.form-input wire:model="newTaskTitle" :label="__('tasks.task_title')" maxlength="255" required autofocus class="col-span-2" :data-test="$testPrefix.'-title-input'" />

            @if ($places !== null)
                <x-ui.form-select wire:model="newTaskTarget" :label="__('tasks.goes_to')" :help="__('tasks.goes_to_help')" required searchable :search-label="__('tasks.move_search')" class="col-span-2" :data-test="$testPrefix.'-goes-to'">
                    <option value="program">{{ $places['program']['name'] }} · {{ __('tasks.move_program_itself') }}</option>
                    @foreach ($places['projects'] as $project)
                        <option value="project:{{ $project['id'] }}">{{ $project['name'] }}</option>
                        @foreach ($project['milestones'] as $milestone)
                            <option value="milestone:{{ $milestone['id'] }}">{{ $project['name'] }} › {{ $milestone['name'] }}</option>
                        @endforeach
                    @endforeach
                </x-ui.form-select>
            @endif

            <x-ui.form-segmented
                wire:model="newTaskStatus"
                :label="__('common.status')"
                class="col-span-2"
                :options="[
                    ['value' => App\Enums\TaskStatus::Todo->value, 'label' => App\Enums\TaskStatus::Todo->label(), 'dot' => 'border border-secondary-light', 'test' => $testPrefix.'-status-'.App\Enums\TaskStatus::Todo->value],
                    ['value' => App\Enums\TaskStatus::InProgress->value, 'label' => App\Enums\TaskStatus::InProgress->label(), 'dot' => 'bg-status-active', 'test' => $testPrefix.'-status-'.App\Enums\TaskStatus::InProgress->value],
                    ['value' => App\Enums\TaskStatus::UnderReview->value, 'label' => App\Enums\TaskStatus::UnderReview->label(), 'dot' => 'bg-status-warn', 'test' => $testPrefix.'-status-'.App\Enums\TaskStatus::UnderReview->value],
                    ['value' => App\Enums\TaskStatus::Done->value, 'label' => App\Enums\TaskStatus::Done->label(), 'dot' => 'bg-status-good', 'test' => $testPrefix.'-status-'.App\Enums\TaskStatus::Done->value],
                ]"
            />

            <x-ui.form-segmented
                wire:model="newTaskPriority"
                :label="__('tasks.priority')"
                :options="[
                    ['value' => App\Enums\TaskPriority::Low->value, 'label' => App\Enums\TaskPriority::Low->label(), 'icon' => 'flag', 'iconClass' => 'text-secondary-light', 'test' => $testPrefix.'-priority-'.App\Enums\TaskPriority::Low->value],
                    ['value' => App\Enums\TaskPriority::Medium->value, 'label' => App\Enums\TaskPriority::Medium->label(), 'icon' => 'flag', 'iconClass' => 'text-status-warn', 'test' => $testPrefix.'-priority-'.App\Enums\TaskPriority::Medium->value],
                    ['value' => App\Enums\TaskPriority::High->value, 'label' => App\Enums\TaskPriority::High->label(), 'icon' => 'flag', 'iconClass' => 'text-status-critical', 'test' => $testPrefix.'-priority-'.App\Enums\TaskPriority::High->value],
                ]"
            />

            <x-ui.form-input wire:model="newTaskDueDate" :label="__('tasks.due_date_2')" type="date" />

            <x-ui.form-multiselect wire:model="newTaskAssignedTo" :label="__('tasks.assignees')" :placeholder="__('common.select')" class="col-span-2" :data-test="$testPrefix.'-assignee-picker'" employees />
        </div>
    @endif

    <x-slot:actions>
        <x-ui.button type="submit" tone="primary" :data-test="'save-'.$testPrefix.'-button'">{{ __('tasks.add_task') }}</x-ui.button>
    </x-slot:actions>
</x-ui.modal>
