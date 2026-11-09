{{-- The status field in the properties column on ⚡show.blade.php. Editors and assignees move it in place (TaskWorkflow decides which moves are allowed); an approver sending an Under review task back writes a note first. Everyone else reads it. --}}
@use('App\Enums\TaskStatus')
<div class="grid gap-1.5 px-4 py-3">
    <p class="{{ $propertyLabelClass }}" id="task-edit-status-label">{{ __('common.status') }}</p>

    @if ($canUpdateTaskStatus)
        <div class="grid grid-cols-2 gap-1.5" role="radiogroup" aria-labelledby="task-edit-status-label">
            @foreach (TaskStatus::cases() as $statusCase)
                @php
                    $isStatusSelected = $taskStatus === $statusCase->value;
                @endphp
                <button
                    type="button"
                    role="radio"
                    aria-checked="{{ $isStatusSelected ? 'true' : 'false' }}"
                    wire:click="changeStatus('{{ $statusCase->value }}')"
                    wire:loading.attr="disabled"
                    wire:target="changeStatus"
                    @class([$chipClass, $chipSelectedClass => $isStatusSelected, $chipIdleClass => ! $isStatusSelected])
                    data-test="task-edit-status-{{ $statusCase->value }}"
                >
                    {{ $statusCase->label() }}
                </button>
            @endforeach
        </div>
        <flux:error name="taskStatus" />

        @if ($this->reviewNoteNeeded())
            <div class="mt-1.5 grid gap-2" data-test="task-send-back-form">
                <flux:textarea wire:model="reviewNote" :label="__('tasks.workflow.send_back_note')" rows="2" maxlength="200" data-test="task-review-note" />
                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" tone="ghost" size="sm" wire:click="cancelStatusChange" data-test="task-cancel-send-back-button">{{ __('common.cancel') }}</x-ui.button>
                    <x-ui.button type="button" tone="primary" size="sm" wire:click="sendBack" data-test="task-send-back-button">{{ __('tasks.send_back') }}</x-ui.button>
                </div>
            </div>
        @endif
    @else
        <div><x-ui.enum-badge :case="$this->task->status" /></div>
    @endif
</div>
