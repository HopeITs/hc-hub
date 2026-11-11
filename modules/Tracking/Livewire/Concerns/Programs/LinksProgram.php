<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Programs;

use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Core\Models\Program;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Models\Strategy;

/**
 * The "Link strategy and manager" dialog on programs/show.
 *
 * The program itself (its name, whether it exists) belongs to HR, so only the
 * two tracking-side links are editable, and either may be left empty.
 *
 * @property int $programId
 */
trait LinksProgram
{
    public bool $showProgramModal = false;

    public string $strategyId = '';

    public string $managerId = '';

    #[Locked]
    public string $originalStrategyId = '';

    #[Locked]
    public string $originalManagerId = '';

    public function openLinkModal(): void
    {
        $program = Program::query()->select(['id', 'strategy_id', 'manager_id'])->findOrFail($this->programId);

        Gate::authorize('update', $program);

        $this->resetValidation();
        $this->strategyId = $this->originalStrategyId = (string) $program->strategy_id;
        $this->managerId = $this->originalManagerId = (string) $program->manager_id;
        $this->showProgramModal = true;
    }

    public function saveProgram(): void
    {
        $program = Program::query()->findOrFail($this->programId);

        Gate::authorize('update', $program);

        $validated = $this->validate([
            'strategyId' => ['nullable', 'integer', Rule::exists(Strategy::class, 'id')],
            'managerId' => ['nullable', 'integer', Rule::exists(Employee::class, 'id')],
        ], [
            'strategyId.exists' => __('programs.the_selected_strategy_is_no_longer_available'),
            'managerId.exists' => __('programs.the_selected_manager_is_no_longer_available'),
        ]);

        $program->update([
            'strategy_id' => blank($validated['strategyId']) ? null : (int) $validated['strategyId'],
            'manager_id' => blank($validated['managerId']) ? null : (int) $validated['managerId'],
        ]);

        $this->closeProgramModal();
        unset($this->program);

        Flux::toast(variant: 'success', text: __('programs.program_assignment_updated'));
    }

    public function closeProgramModal(): void
    {
        $this->showProgramModal = false;
        $this->resetValidation();
        $this->strategyId = $this->managerId = $this->originalStrategyId = $this->originalManagerId = '';
    }

    /** @return Collection<int, Strategy> */
    #[Computed]
    public function strategyOptions(): Collection
    {
        return Strategy::query()
            ->select(['id', 'name', 'start_year', 'end_year'])
            ->orderByDesc('start_year')
            ->orderByDesc('end_year')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    /**
     * A strategy as one short name: the years are already in names like
     * "Country Strategy 2026-2030", so they are added only when the name lacks them.
     */
    public function strategyLabel(Strategy $strategy): string
    {
        return str_contains($strategy->name, (string) $strategy->start_year)
            ? $strategy->name
            : $strategy->name.' ('.$strategy->start_year.'–'.$strategy->end_year.')';
    }
}
