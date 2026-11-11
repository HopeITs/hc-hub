<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Strategies;

use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Tracking\Models\Strategy;

/**
 * The create / edit strategy dialog, shared by strategies/index and
 * strategies/show. The page renders
 * resources/views/pages/strategies/_strategy-form-modal.blade.php.
 *
 * Overlapping years are allowed (a country plan and a regional plan may run
 * side by side); the form only warns about them.
 *
 * @property-read bool $canCreateStrategies
 * @property-read bool $canEditStrategies
 * @property-read list<array{name: string, start_year: int, end_year: int}> $overlappingStrategies
 */
trait ManagesStrategyForm
{
    public bool $showStrategyModal = false;

    #[Locked]
    public ?int $editingStrategyId = null;

    public string $name = '';

    public string $startYear = '';

    public string $endYear = '';

    /**
     * A year typed after a failed save clears that field's stale error.
     */
    public function updatedStartYear(): void
    {
        $this->resetValidation('startYear');
    }

    public function updatedEndYear(): void
    {
        $this->resetValidation('endYear');
    }

    /**
     * Whether the current user may create strategies (strategies.create).
     */
    #[Computed]
    public function canCreateStrategies(): bool
    {
        return auth()->user()->can('strategies.create');
    }

    /**
     * Whether the current user may edit strategies (strategies.update).
     */
    #[Computed]
    public function canEditStrategies(): bool
    {
        return auth()->user()->can('strategies.update');
    }

    public function openCreateModal(): void
    {
        Gate::authorize('create', Strategy::class);

        $this->resetStrategyForm();
        $this->showStrategyModal = true;
    }

    public function openEditModal(int $strategyId): void
    {
        $strategy = Strategy::query()->select(['id', 'name', 'start_year', 'end_year'])->findOrFail($strategyId);

        Gate::authorize('update', $strategy);

        $this->resetValidation();
        $this->editingStrategyId = $strategy->id;
        $this->name = $strategy->name;
        $this->startYear = (string) $strategy->start_year;
        $this->endYear = (string) $strategy->end_year;
        $this->showStrategyModal = true;
    }

    public function saveStrategy(): void
    {
        $strategy = $this->editingStrategyId === null
            ? new Strategy
            : Strategy::query()->findOrFail($this->editingStrategyId);

        Gate::authorize($strategy->exists ? 'update' : 'create', $strategy->exists ? $strategy : Strategy::class);

        $this->name = trim($this->name);

        // Deliberately no unique rule: strategies.name is a plain non-unique
        // VARCHAR by standing decision.
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'startYear' => ['required', 'integer', 'min:1900', 'max:2100'],
            'endYear' => ['required', 'integer', 'min:1900', 'max:2100', 'gte:startYear'],
        ], [
            'name.required' => __('strategies.enter_a_strategy_name'),
            'startYear.required' => __('strategies.enter_a_start_year'),
            'endYear.required' => __('strategies.enter_an_end_year'),
            'endYear.gte' => __('strategies.end_year_must_not_be_before_the_start_year'),
        ]);

        $strategy->fill([
            'name' => $validated['name'],
            'start_year' => (int) $validated['startYear'],
            'end_year' => (int) $validated['endYear'],
        ])->save();

        $wasRecentlyCreated = $strategy->wasRecentlyCreated;

        $this->showStrategyModal = false;
        $this->resetStrategyForm();

        Flux::toast(
            variant: 'success',
            text: $wasRecentlyCreated ? __('strategies.strategy_created') : __('strategies.strategy_updated'),
        );
    }

    public function closeStrategyModal(): void
    {
        $this->showStrategyModal = false;
        $this->resetStrategyForm();
    }

    protected function resetStrategyForm(): void
    {
        $this->resetValidation();
        $this->editingStrategyId = null;
        $this->name = '';
        $this->startYear = '';
        $this->endYear = '';
    }

    /**
     * Strategies whose years overlap the period typed in the form, for the
     * warning under it.
     *
     * @return list<array{name: string, start_year: int, end_year: int}>
     */
    #[Computed]
    public function overlappingStrategies(): array
    {
        if (! ctype_digit($this->startYear) || ! ctype_digit($this->endYear) || (int) $this->endYear < (int) $this->startYear) {
            return [];
        }

        return array_values(Strategy::query()
            ->select(['name', 'start_year', 'end_year'])
            ->when($this->editingStrategyId !== null, fn ($query) => $query->whereKeyNot($this->editingStrategyId))
            ->where('start_year', '<=', (int) $this->endYear)
            ->where('end_year', '>=', (int) $this->startYear)
            ->orderBy('start_year')
            ->get()
            ->map(fn (Strategy $strategy): array => ['name' => $strategy->name, 'start_year' => $strategy->start_year, 'end_year' => $strategy->end_year])
            ->all());
    }
}
