<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Employees;

use App\Support\EmployeeLookup;
use Illuminate\Database\Query\Builder;
use Modules\Core\Models\User;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;

/**
 * HR's answers to what an account asks about its employee (App\Support\EmployeeLookup).
 */
final class HrEmployeeLookup implements EmployeeLookup
{
    public function avatarUrl(?int $employeeId): string
    {
        return Employee::avatarUrl($employeeId);
    }

    public function photoUrl(?int $employeeId): ?string
    {
        return Employee::photoUrl($employeeId);
    }

    /**
     * @return list<int>
     */
    public function idsInDepartment(int $departmentId): array
    {
        return Department::employeeIdsInDepartment($departmentId);
    }

    public function idsNamedLike(string $word): Builder
    {
        return Employee::query()->select('employees.id')->whereNameLike($word, false)->toBase();
    }

    public function idsInHrReachOf(User $user): ?Builder
    {
        $cityIds = HrAccess::cityIds($user);

        return $cityIds === null ? null : Employee::query()->select('employees.id')->whereIn('city_id', $cityIds)->toBase();
    }

    /**
     * @param  list<int>  $employeeIds
     * @return list<int>
     */
    public function activeIds(array $employeeIds = []): array
    {
        return Employee::query()
            ->where('is_active', true)
            ->when($employeeIds !== [], fn ($query) => $query->whereKey($employeeIds))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    public function activeIdsIn(?array $cityIds): Builder
    {
        return Employee::query()
            ->select('employees.id')
            ->where('employees.is_active', true)
            ->forCity($cityIds)
            ->toBase();
    }
}
