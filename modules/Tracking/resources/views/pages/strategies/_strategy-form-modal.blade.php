{{-- The create / edit strategy dialog (Modules\Tracking\Livewire\Concerns\Strategies\ManagesStrategyForm). --}}
@if ($this->canCreateStrategies || $this->canEditStrategies)
    <x-ui.modal
        wire:model="showStrategyModal"
        size="md"
        :title="$editingStrategyId === null ? __('strategies.add_strategy') : __('strategies.edit_strategy')"
        submit="saveStrategy"
        close="closeStrategyModal"
        required-note
    >
        @if ($showStrategyModal)
            <div class="grid grid-cols-2 gap-x-4 gap-y-4">
                <x-ui.form-input wire:model="name" :label="__('strategies.strategy_name')" maxlength="255" required autofocus class="col-span-2" />
                <x-ui.form-input wire:model.live.blur="startYear" :label="__('strategies.period_start')" type="number" min="1900" max="2100" required />
                <x-ui.form-input wire:model.live.blur="endYear" :label="__('strategies.period_end')" type="number" min="1900" max="2100" required />

                @if ($this->overlappingStrategies !== [])
                    <div class="col-span-2 flex items-start gap-2 rounded-md bg-status-warn-soft px-3 py-2 text-sm text-secondary-dark dark:bg-surface-2 dark:text-ink" role="status" data-test="strategy-overlap-warning">
                        <flux:icon.exclamation-triangle variant="mini" class="mt-0.5 size-4 shrink-0 text-status-warn" />
                        <span>{{ __('strategies.overlaps_with', ['names' => collect($this->overlappingStrategies)->map(fn (array $other): string => "\u{2068}{$other['name']}\u{2069} \u{2066}({$other['start_year']}–{$other['end_year']})\u{2069}")->implode(__('strategies.list_separator'))]) }}</span>
                    </div>
                @endif
            </div>
        @endif

        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="save-strategy-button">
                {{ $editingStrategyId === null ? __('strategies.create_strategy') : __('common.save_changes') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.modal>
@endif
