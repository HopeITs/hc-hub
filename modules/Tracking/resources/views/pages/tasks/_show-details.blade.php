{{-- The description panel on ⚡show.blade.php. Editors change the title and description in place; everyone else reads them. --}}
<div x-data="{ editing: false }" x-on:task-details-saved.window="editing = false">
    <x-ui.panel :title="__('common.description')">
        @if ($canEditTask)
            <x-slot:toolbar>
                <x-ui.button tone="ghost" size="sm" icon="pencil-square" x-show="! editing" x-on:click="editing = true" data-test="task-edit-details-button">{{ __('tasks.edit_description') }}</x-ui.button>
            </x-slot:toolbar>
        @endif

        <div x-show="! editing">
            @if ($this->task->description !== null)
                <p class="whitespace-pre-line p-4 text-sm text-secondary-medium dark:text-ink-muted">{{ $this->task->description }}</p>
            @else
                <p class="p-4 text-sm text-secondary-medium dark:text-ink-muted" data-test="task-no-description">{{ __('tasks.no_description') }}</p>
            @endif
        </div>

        @if ($canEditTask)
            <form wire:submit="saveDetails" x-show="editing" style="display: none" class="grid gap-4 p-4" data-test="task-details-form">
                <x-ui.input wire:model="title" :label="__('tasks.task_title')" type="text" maxlength="255" required data-test="task-edit-title-input" />

                <flux:textarea wire:model="description" :label="__('common.description')" rows="5" class="border-secondary-light! bg-white! text-secondary-dark! dark:border-field-line! dark:bg-surface-2! dark:text-ink!" />

                <div class="flex justify-end gap-2">
                    <x-ui.button type="button" tone="ghost" x-on:click="editing = false; $wire.cancelDetails()" data-test="task-cancel-details-button">{{ __('common.cancel') }}</x-ui.button>
                    <x-ui.button type="submit" tone="primary" data-test="task-save-details-button">{{ __('common.save_changes') }}</x-ui.button>
                </div>
            </form>
        @endif
    </x-ui.panel>
</div>
