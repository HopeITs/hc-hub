<?php

namespace App\Livewire\Forms\Employee;

use App\Enums\ContractType as ContractTypeEnum;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Employee;
use App\Models\Position;
use Closure;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DraftContractForm extends Form
{
    #[Validate('required')]
    public ?int $position_id = null;

    /**
     * Create a placeholder contract (type = Draft) reserving $position for
     * $employee: no schedule, no pay, no capacity check. It is meant to be
     * upgraded into a full contract later from the same row.
     */
    public function createDraft(Employee $employee): Contract
    {
        $this->validate([
            'position_id' => ['required', $this->belongsToEmployeeCityRule($employee)],
        ]);

        $volunteerType = ContractType::all()->first(fn (ContractType $type): bool => $type->category === 'annual');

        return Contract::create([
            'employee_id' => $employee->id,
            'position_id' => (int) $this->position_id,
            'contract_type_id' => $volunteerType?->id,
            'type' => ContractTypeEnum::Draft,
            'is_active' => true,
        ]);
    }

    /**
     * Position::scopeInCity() is the single source of truth for city
     * scoping (CLAUDE.md) — the dropdown offering positions and this rule
     * validating the chosen one must both go through it, or a request that
     * bypasses the dropdown could reserve a position in another city.
     */
    private function belongsToEmployeeCityRule(Employee $employee): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($employee): void {
            if (! Position::query()->inCity($employee->city_id)->whereKey($value)->exists()) {
                $fail(__('validation.exists', ['attribute' => 'position']));
            }
        };
    }
}
