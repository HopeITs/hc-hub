<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Strategies;

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Core\Models\Program;
use Modules\Tracking\Models\Strategy;

/**
 * The "link programs" picker, shared by strategies/index and strategies/show:
 * tick the programs that belong to a strategy, untick one to unlink it. A
 * ticked program from another strategy moves here. The page renders
 * resources/views/pages/strategies/_programs-modal.blade.php.
 *
 * @property-read bool $canLinkPrograms
 * @property-read Strategy|null $managingStrategy
 * @property-read list<array{id: int, name: string, strategy_id: int|null, strategy_name: string|null}> $programOptions
 * @property-read list<array{name: string, strategy_name: string}> $programsMovingHere
 */
trait LinksStrategyPrograms
{
    public bool $showProgramsModal = false;

    #[Locked]
    public ?int $managingStrategyId = null;

    /** @var list<string> */
    public array $selectedProgramIds = [];

    /**
     * Whether the current user may link programs to strategies (programs.update).
     */
    #[Computed]
    public function canLinkPrograms(): bool
    {
        return auth()->user()->can('programs.update');
    }

    /**
     * Open the picker of the programs linked to one strategy, with its current
     * programs already ticked.
     */
    public function openProgramsModal(int $strategyId): void
    {
        Gate::authorize('programs.update');

        $strategy = Strategy::query()->select(['id'])->findOrFail($strategyId);

        $this->resetValidation();
        $this->managingStrategyId = $strategy->id;
        $this->selectedProgramIds = array_values(Program::query()
            ->where('strategy_id', $strategy->id)
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all());
        $this->showProgramsModal = true;
    }

    /**
     * The strategy the programs picker is open for.
     */
    #[Computed]
    public function managingStrategy(): ?Strategy
    {
        return $this->managingStrategyId === null
            ? null
            : Strategy::query()->select(['id', 'name'])->find($this->managingStrategyId);
    }

    /**
     * Every program, with the strategy it sits in now, for the picker.
     *
     * @return list<array{id: int, name: string, strategy_id: int|null, strategy_name: string|null}>
     */
    #[Computed]
    public function programOptions(): array
    {
        return array_values(Program::query()
            ->select(['id', 'strategy_id'])
            ->with('strategy:id,name')
            ->orderByName()
            ->get()
            ->map(fn (Program $program): array => [
                'id' => $program->id,
                'name' => $program->name,
                'strategy_id' => $program->strategy_id,
                'strategy_name' => $program->strategy?->name,
            ])
            ->all());
    }

    /**
     * Ticked programs that sit in another strategy now and will move here.
     *
     * @return list<array{name: string, strategy_name: string}>
     */
    #[Computed]
    public function programsMovingHere(): array
    {
        $selected = array_map('intval', $this->selectedProgramIds);

        return array_values(collect($this->programOptions)
            ->filter(fn (array $option): bool => in_array($option['id'], $selected, true)
                && $option['strategy_id'] !== null
                && $option['strategy_id'] !== $this->managingStrategyId)
            ->map(fn (array $option): array => ['name' => $option['name'], 'strategy_name' => (string) $option['strategy_name']])
            ->all());
    }

    /**
     * Link the ticked programs to the strategy and unlink the ones unticked.
     * A ticked program from another strategy moves here.
     */
    public function savePrograms(): void
    {
        Gate::authorize('programs.update');

        $strategy = Strategy::query()->select(['id'])->findOrFail($this->managingStrategyId);

        $validated = $this->validate([
            'selectedProgramIds' => ['array'],
            'selectedProgramIds.*' => ['integer', Rule::exists(Program::class, 'id')],
        ]);

        $programIds = array_map('intval', $validated['selectedProgramIds']);

        DB::transaction(function () use ($strategy, $programIds): void {
            Program::query()
                ->where('strategy_id', $strategy->id)
                ->whereNotIn('id', $programIds)
                ->update(['strategy_id' => null]);

            Program::query()
                ->whereIn('id', $programIds)
                ->update(['strategy_id' => $strategy->id]);
        });

        $this->closeProgramsModal();

        Flux::toast(variant: 'success', text: __('strategies.programs_saved'));
    }

    public function closeProgramsModal(): void
    {
        $this->resetValidation();
        $this->showProgramsModal = false;
        $this->managingStrategyId = null;
        $this->selectedProgramIds = [];
    }
}
