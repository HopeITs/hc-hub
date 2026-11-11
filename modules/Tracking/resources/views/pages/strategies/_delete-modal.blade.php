{{-- The delete-strategy confirmation (Modules\Tracking\Livewire\Concerns\Strategies\DeletesStrategy). --}}
@if ($this->canDeleteStrategies)
    <x-ui.confirm-modal
        wire:model="showDeleteModal"
        :title="$this->deletingStrategyName === null ? __('strategies.delete_this_strategy') : __('strategies.delete_named_strategy', ['name' => $this->deletingStrategyName])"
        :message="__('strategies.delete_strategy_cannot_be_undone')"
        close="cancelDelete"
        confirm="deleteStrategy"
        :confirm-label="__('strategies.delete_strategy')"
        confirm-test="confirm-delete-strategy"
    />
@endif
