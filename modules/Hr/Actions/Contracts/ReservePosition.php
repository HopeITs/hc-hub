<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Contracts;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * Hold a position for a new employee with a draft contract: a placeholder
 * with no dates and no pay, which the contract form later turns into the
 * real contract. A draft holds no seat in the position's headcount.
 */
class ReservePosition
{
    public function handle(Employee $employee, int $positionId, ?int $cityId, ?int $programId): Contract
    {
        Gate::authorize('update', $employee);

        if (! HrAccess::canAccessCity(Auth::user(), $cityId)) {
            throw ValidationException::withMessages(['draftPositionId' => __('hr_employees.validation.city_scope')]);
        }

        $exists = Position::query()
            ->inCity($cityId ?? 0)
            ->when($programId !== null, fn ($query) => $query->where('program_id', $programId))
            ->whereKey($positionId)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages(['draftPositionId' => __('validation.exists', ['attribute' => __('hr_employees.fields.position')])]);
        }

        $annualTypeId = collect(config('contracts.map_by_id', ContractType::CATEGORY_BY_ID))->search('annual');

        return Contract::create([
            'employee_id' => $employee->id,
            'position_id' => $positionId,
            'contract_type_id' => $annualTypeId === false ? ContractType::query()->value('id') : $annualTypeId,
            'type' => ContractTypeEnum::Draft->value,
            'is_active' => true,
        ]);
    }
}
