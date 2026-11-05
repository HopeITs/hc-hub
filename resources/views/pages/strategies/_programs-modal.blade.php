{{-- The link-programs picker (App\Livewire\Concerns\Strategies\LinksStrategyPrograms). --}}
@if ($this->canLinkPrograms)
    <x-ui.modal
        wire:model="showProgramsModal"
        size="md"
        :title="$this->managingStrategy === null ? __('strategies.manage_programs') : __('strategies.programs_of_named_strategy', ['name' => $this->managingStrategy->name])"
        :description="__('strategies.programs_modal_hint')"
        submit="savePrograms"
        close="closeProgramsModal"
    >
        @if ($showProgramsModal)
            <div class="flex flex-col gap-3" x-data="{ programSearch: '' }" data-test="strategy-programs-modal">
                <x-ui.input
                    type="search"
                    x-model="programSearch"
                    icon="magnifying-glass"
                    :placeholder="__('strategies.search_programs')"
                    :aria-label="__('strategies.search_programs')"
                    data-test="strategy-programs-search"
                />

                <p class="type-meta text-secondary-medium dark:text-ink-muted" aria-live="polite">
                    {{ trans_choice('strategies.count_programs_selected', count($selectedProgramIds), ['count' => count($selectedProgramIds)]) }}
                </p>

                <ul class="flex max-h-80 flex-col divide-y divide-board-line overflow-y-auto rounded-lg border border-board-line">
                    @forelse ($this->programOptions as $option)
                        <li
                            data-program-option
                            wire:key="strategy-program-option-{{ $option['id'] }}"
                            x-show="programSearch === '' || $el.textContent.toLowerCase().includes(programSearch.toLowerCase())"
                        >
                            <label class="flex cursor-pointer items-center gap-3 px-3 py-2.5 hover:bg-ground">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectedProgramIds"
                                    value="{{ $option['id'] }}"
                                    class="size-4 shrink-0 accent-primary"
                                    data-test="strategy-program-option"
                                />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-secondary-dark dark:text-ink">{{ $option['name'] }}</span>
                                    @if ($option['strategy_id'] !== null && $option['strategy_id'] !== $managingStrategyId)
                                        <span class="type-meta block truncate text-secondary-medium dark:text-ink-muted">{{ __('strategies.now_in', ['name' => $option['strategy_name']]) }}</span>
                                    @endif
                                </span>
                            </label>
                        </li>
                    @empty
                        <li class="px-3 py-6 text-center text-sm text-secondary-medium dark:text-ink-muted">{{ __('strategies.no_programs_to_link') }}</li>
                    @endforelse
                    <li
                        x-cloak
                        x-show="programSearch !== '' && ! [...$root.querySelectorAll('[data-program-option]')].some((option) => option.textContent.toLowerCase().includes(programSearch.toLowerCase()))"
                        class="px-3 py-6 text-center text-sm text-secondary-medium dark:text-ink-muted"
                    >{{ __('strategies.no_programs_match') }}</li>
                </ul>

                @if ($this->programsMovingHere !== [])
                    <div class="flex items-start gap-2 rounded-md bg-status-warn-soft px-3 py-2 text-sm text-secondary-dark dark:bg-surface-2 dark:text-ink" role="status" data-test="strategy-programs-move-warning">
                        <flux:icon.exclamation-triangle variant="mini" class="mt-0.5 size-4 shrink-0 text-status-warn" />
                        <span>{{ __('strategies.programs_will_move', ['names' => collect($this->programsMovingHere)->map(fn (array $moving): string => "\u{2068}{$moving['name']}\u{2069} (\u{2068}{$moving['strategy_name']}\u{2069})")->implode(__('strategies.list_separator'))]) }}</span>
                    </div>
                @endif
            </div>
        @endif

        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="save-strategy-programs">
                {{ __('common.save_changes') }}
            </x-ui.button>
        </x-slot:actions>
    </x-ui.modal>
@endif
