<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Settings;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Modules\Hr\Models\Contract;
use Modules\Hr\Support\Settings\HrAccess;
use Modules\Hr\Support\Settings\OrgCounts;

/**
 * The "who works here now" list of a program or center page: one row per
 * active contract dated today, searchable by the employee's name, narrowed
 * to a regional HR role's governorates and shown only to employees.view.
 *
 * The page supplies assignmentScope(): the position column => value pair
 * the list belongs to.
 */
trait ListsAssignedEmployees
{
    public string $employeeSearch = '';

    public function updatedEmployeeSearch(): void
    {
        $this->resetPage('people');
    }

    /** @return array<string, int> */
    abstract protected function assignmentScope(): array;

    #[Computed]
    public function canViewEmployees(): bool
    {
        return (bool) Auth::user()?->can('employees.view');
    }

    /** @return LengthAwarePaginator<int, Contract>|null */
    #[Computed]
    public function assignments(): ?LengthAwarePaginator
    {
        if (! $this->canViewEmployees) {
            return null;
        }

        $term = trim($this->employeeSearch);

        return OrgCounts::activeAssignments(HrAccess::for(Auth::user())->cityIds(), $this->assignmentScope())
            ->select(['contracts.id', 'contracts.employee_id', 'contracts.position_id', 'contracts.contract_type_id'])
            ->with([
                'employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar',
                'position:id,title_en,title_ar,program_id,center_id,city_id',
                'contractType:id,name_en,name_ar',
            ])
            ->when($term !== '', fn (Builder $contracts) => $contracts->whereHas('employee', fn (Builder $employee) => $employee->whereNameLike($term)))
            ->orderBy('positions.title_en')
            ->orderBy('contracts.id')
            ->paginate(15, pageName: 'people');
    }
}
