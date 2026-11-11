<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Strategies;

use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Tracking\Models\Strategy;

/**
 * The delete-strategy confirmation, shared by strategies/index and
 * strategies/show. The page renders
 * resources/views/pages/strategies/_delete-modal.blade.php and may override
 * strategyDeleted() to act after a successful delete (the strategy page
 * leaves for the list).
 *
 * @property-read bool $canDeleteStrategies
 * @property-read string|null $deletingStrategyName
 */
trait DeletesStrategy
{
    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingStrategyId = null;

    /**
     * Whether the current user may delete strategies (strategies.delete).
     */
    #[Computed]
    public function canDeleteStrategies(): bool
    {
        return auth()->user()->can('strategies.delete');
    }

    /**
     * Whether this strategy may be deleted.
     *
     * programs.strategy_id and strategic_pillars.strategy_id RESTRICT: a
     * strategy carrying any programs or pillars stays.
     */
    protected function canDelete(int $strategyId): bool
    {
        $strategy = Strategy::query()->select(['id'])->withCount(['programs', 'pillars'])->find($strategyId);

        return $strategy !== null && $strategy->programs_count === 0 && $strategy->pillars_count === 0;
    }

    public function confirmDelete(int $strategyId): void
    {
        Gate::authorize('delete', Strategy::query()->select(['id'])->findOrFail($strategyId));

        $this->deletingStrategyId = $strategyId;
        $this->showDeleteModal = true;
    }

    public function deleteStrategy(): void
    {
        if ($this->deletingStrategyId === null) {
            return;
        }

        $strategy = Strategy::query()->findOrFail($this->deletingStrategyId);

        Gate::authorize('delete', $strategy);

        if (! $this->canDelete($this->deletingStrategyId)) {
            $this->showDeleteModal = false;
            $this->deletingStrategyId = null;

            Flux::toast(
                variant: 'danger',
                text: $strategy->programs()->exists()
                    ? __('strategies.this_strategy_has_linked_programs_and_cannot_be_deleted')
                    : __('strategies.strategy_has_pillars'),
            );

            return;
        }

        $strategy->delete();

        $this->showDeleteModal = false;
        $this->deletingStrategyId = null;

        Flux::toast(variant: 'success', text: __('strategies.strategy_deleted'));

        $this->strategyDeleted();
    }

    /**
     * Called after a strategy was deleted. The list stays where it is.
     */
    protected function strategyDeleted(): void {}

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingStrategyId = null;
    }

    /**
     * The name of the strategy the delete dialog is asking about.
     */
    #[Computed]
    public function deletingStrategyName(): ?string
    {
        return $this->deletingStrategyId === null
            ? null
            : Strategy::query()->whereKey($this->deletingStrategyId)->value('name');
    }
}
