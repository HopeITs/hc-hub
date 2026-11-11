@php
    /*
     * The one "Discard your changes?" question asked before a page with
     * unsaved work goes away (resources/js/unsaved.js opens it by name and
     * reads the answer). Once per page, in the layout. Cancel, the X, Esc and
     * a backdrop click all mean "keep editing"; only the Discard button leaves.
     */
@endphp

<x-ui.modal
    size="sm"
    name="unsaved-leave"
    :title="__('common.discard_changes_question')"
    :description="__('common.unsaved_leave_text')"
    :cancel-label="__('common.keep_editing')"
    data-unsaved-prompt
>
    <x-slot:mark>
        <span class="confirm-mark bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink" aria-hidden="true">
            <flux:icon.exclamation-triangle variant="mini" class="size-4" />
        </span>
    </x-slot:mark>

    <x-slot:actions>
        <x-ui.button type="button" tone="primary" data-unsaved-discard>{{ __('common.discard') }}</x-ui.button>
    </x-slot:actions>
</x-ui.modal>
