<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Settings;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Support\Settings\HrAccess;
use Modules\Hr\Support\Settings\OrgCounts;

/**
 * The "who works here now" list of a program or center page: one row per
 * active contract dated today, searchable by the employee's name, narrowed
 * to a regional HR role's governorates and shown only to employees.view.
 *
 * Head office first, then the governorates in their code order (Aleppo 001,
 * Homs 002, ...), each by job level grade (Program Manager first). Filters
 * narrow by center (on a program page), department and job level, and offer
 * only what the list's own contracts use, so no choice comes back empty.
 *
 * The page supplies assignmentScope(): the position column => value pair
 * the list belongs to. A page whose list is wider than one column (a
 * center's shared volunteers) overrides assignmentQuery().
 */
trait ListsAssignedEmployees
{
    public string $employeeSearch = '';

    public string $employeeCenter = '';

    public string $employeeDepartment = '';

    public string $employeeLevel = '';

    public function updatedEmployeeSearch(): void
    {
        $this->resetPage('people');
    }

    public function updatedEmployeeCenter(): void
    {
        $this->resetPage('people');
    }

    public function updatedEmployeeDepartment(): void
    {
        $this->resetPage('people');
    }

    public function updatedEmployeeLevel(): void
    {
        $this->resetPage('people');
    }

    public function resetEmployeeFilters(): void
    {
        $this->reset('employeeSearch', 'employeeCenter', 'employeeDepartment', 'employeeLevel');
        $this->resetPage('people');
    }

    /** @return array<string, int> */
    abstract protected function assignmentScope(): array;

    /**
     * The active contracts the list is drawn from, joined to their position.
     *
     * @param  list<int>|null  $cityIds
     * @return Builder<Contract>
     */
    protected function assignmentQuery(?array $cityIds): Builder
    {
        return OrgCounts::activeAssignments($cityIds, $this->assignmentScope());
    }

    /** A center filter means something only where the list spans several centers. */
    protected function filtersByCenter(): bool
    {
        return ! array_key_exists('center_id', $this->assignmentScope());
    }

    #[Computed]
    public function canViewEmployees(): bool
    {
        return (bool) Auth::user()?->can('employees.view');
    }

    /**
     * The choices each filter offers: id => name, only those in use.
     *
     * @return array{centers: array<int, string>, departments: array<int, string>, levels: array<int, string>}
     */
    #[Computed]
    public function assignmentFilters(): array
    {
        if (! $this->canViewEmployees) {
            return ['centers' => [], 'departments' => [], 'levels' => []];
        }

        $used = fn (string $column): array => $this->assignmentQuery(HrAccess::for(Auth::user())->cityIds())
            ->toBase()
            ->whereNotNull('positions.'.$column)
            ->distinct()
            ->pluck('positions.'.$column)
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $centers = $this->filtersByCenter()
            ? collect($used('center_id'))->mapWithKeys(fn (int $id): array => [$id => Center::nameOf($id)])->filter()->sort()->all()
            : [];

        return [
            'centers' => $centers,
            'departments' => collect($used('department_id'))->mapWithKeys(fn (int $id): array => [$id => Department::nameOf($id)])->filter()->sort()->all(),
            'levels' => JobLevel::query()
                ->whereIn('id', $used('job_level_id'))
                ->orderBy('grade')
                ->orderBy('id')
                ->get(['id', 'name_en', 'name_ar'])
                ->mapWithKeys(fn (JobLevel $level): array => [$level->id => (string) $level->name])
                ->all(),
        ];
    }

    /** @return LengthAwarePaginator<int, Contract>|null */
    #[Computed]
    public function assignments(): ?LengthAwarePaginator
    {
        if (! $this->canViewEmployees) {
            return null;
        }

        $term = trim($this->employeeSearch);
        $filters = $this->assignmentFilters;
        $filter = fn (string $value, array $options): ?int => array_key_exists((int) $value, $options) ? (int) $value : null;
        $centerId = $filter($this->employeeCenter, $filters['centers']);
        $departmentId = $filter($this->employeeDepartment, $filters['departments']);
        $levelId = $filter($this->employeeLevel, $filters['levels']);
        $nationalId = City::idsBySlug()[City::NATIONAL_SLUG] ?? 0;
        // Through the grammar: a table prefix would be missed by raw SQL.
        $column = fn (string $name): string => DB::connection()->getQueryGrammar()->wrap($name);

        return $this->assignmentQuery(HrAccess::for(Auth::user())->cityIds())
            ->select(['contracts.id', 'contracts.employee_id', 'contracts.position_id', 'contracts.contract_type_id'])
            ->with([
                'employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar',
                'position:id,title_en,title_ar,program_id,center_id,city_id',
                'contractType:id,name_en,name_ar',
            ])
            ->leftJoin('cities as rank_cities', 'rank_cities.id', '=', 'positions.city_id')
            ->leftJoin('job_levels as rank_levels', 'rank_levels.id', '=', 'positions.job_level_id')
            ->when($term !== '', fn (Builder $contracts) => $contracts->whereHas('employee', fn (Builder $employee) => $employee->whereNameLike($term)))
            ->when($centerId !== null, fn (Builder $contracts) => $contracts->where('positions.center_id', $centerId))
            ->when($departmentId !== null, fn (Builder $contracts) => $contracts->where('positions.department_id', $departmentId))
            ->when($levelId !== null, fn (Builder $contracts) => $contracts->where('positions.job_level_id', $levelId))
            ->orderByRaw("case when {$column('positions.city_id')} = ? then 0 else 1 end", [$nationalId])
            ->orderBy('rank_cities.code')
            ->orderByRaw("coalesce({$column('rank_levels.grade')}, 999)")
            ->orderBy('positions.title_en')
            ->orderBy('contracts.id')
            ->paginate(15, pageName: 'people');
    }
}
