<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Checklists;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\DefaultDeliverable;
use Modules\Hr\Models\Employee;

/**
 * Who hands checklist items out. The service departments (IT, Communication,
 * HR) deliver equipment and take it back; the same rule decides whose
 * finished hand-overs the "Done requests" page lists.
 */
final class ChecklistPeople
{
    /** The columns Employee::name needs. */
    public const array NAME_COLUMNS = ['id', 'first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar'];

    /** Department names whose staff see their department's finished hand-overs. */
    public const array SERVICE_DEPARTMENT_NAMES = ['IT', 'Communication', 'HR'];

    /** Department codes whose staff may be a governorate's default deliverer. */
    public const array SERVICE_DEPARTMENT_CODES = ['IT', 'CT', 'HR'];

    /**
     * Employees with an active contract in a service department.
     *
     * @param  list<string>  $values
     * @return Builder<Employee>
     */
    public static function inServiceDepartments(string $column = 'code', array $values = self::SERVICE_DEPARTMENT_CODES): Builder
    {
        return Employee::query()->whereHas(
            'contracts.position.department',
            fn (Builder $department) => $department->whereIn($column, $values),
        );
    }

    /**
     * The people offered as deliverer or receiver on an employee's checklist:
     * everyone set as a default deliverer, or the service departments' staff
     * when no default exists yet.
     *
     * @return Collection<int, Employee>
     */
    public static function deliverers(): Collection
    {
        $defaults = Employee::query()
            ->whereIn('id', DefaultDeliverable::query()->select('employee_id'))
            ->orderBy(Employee::translatedColumn('first_name'))
            ->get(self::NAME_COLUMNS);

        return $defaults->isNotEmpty()
            ? $defaults
            : self::inServiceDepartments('name', self::SERVICE_DEPARTMENT_NAMES)
                ->orderBy(Employee::translatedColumn('first_name'))
                ->get(self::NAME_COLUMNS);
    }

    /**
     * The service department the user works in (by active contract), with
     * the ids of everyone in it; null when they are in none.
     *
     * @return array{department: string, employeeIds: list<int>}|null
     */
    public static function departmentContext(User $user): ?array
    {
        if ($user->employee_id === null) {
            return null;
        }

        $employee = Employee::query()->whereKey($user->employee_id)->first(['id']);
        $department = $employee?->latestActiveContract()?->position?->department?->name;

        if (! in_array($department, self::SERVICE_DEPARTMENT_NAMES, true)) {
            return null;
        }

        $ids = self::inServiceDepartments('name', [$department])->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();

        return ['department' => $department, 'employeeIds' => array_values($ids)];
    }
}
