<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Search;

use App\Support\Like;
use App\Support\Modules\Contracts\SearchSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * Employees in the Ask bar (roadmap 4.11): found by English or Arabic name
 * or by username, each row opening the profile page.
 *
 * Who is found is the profile page's own rule (EmployeePolicy::viewDirectory),
 * written as a query so nobody is loaded only to be dropped: an active
 * colleague is open to everyone, a former one (or a record still being
 * entered) only to the general manager, the super admin, national HR, and a
 * regional HR role inside its governorates; everyone finds themselves. The
 * national number, phone numbers and pay are never searched or selected.
 */
final class EmployeeSearchSource implements SearchSource
{
    public function key(): string
    {
        return 'employees';
    }

    public function label(): string
    {
        return __('hr_search.employees.label');
    }

    public function icon(): string
    {
        return 'users';
    }

    public function words(): array
    {
        return ['employee', 'employees', 'staff', 'colleague', 'colleagues', 'موظف', 'موظفه', 'موظفين', 'موظفون', 'زميل', 'زملاء'];
    }

    public function allows(User $user): bool
    {
        return true;
    }

    public function search(User $user, string $term, int $limit): array
    {
        if (trim($term) === '' || $limit < 1) {
            return [];
        }

        $nameColumn = app()->getLocale() === 'ar' ? 'employees.first_name_ar' : 'employees.first_name_en';

        $employees = Employee::query()
            ->select([
                'employees.id', 'employees.username', 'employees.city_id', 'employees.is_active',
                'employees.first_name_en', 'employees.last_name_en', 'employees.first_name_ar', 'employees.last_name_ar',
            ])
            ->whereNotNull('employees.username')
            ->where('employees.username', '!=', '')
            ->tap(fn (Builder $query) => $this->visibleTo($query, $user))
            ->where(Like::everyWord($term, fn (Builder|QueryBuilder $fields, string $word) => $fields
                ->where(Like::word('employees.first_name_en', $word))
                ->orWhere(Like::word('employees.last_name_en', $word))
                ->orWhere(Like::word('employees.first_name_ar', $word))
                ->orWhere(Like::word('employees.last_name_ar', $word))
                ->orWhere(Like::word('employees.username', $word)), acrossLayouts: false))
            ->orderByDesc('employees.is_active')
            ->orderBy($nameColumn)
            ->orderBy('employees.id')
            ->limit($limit)
            ->get();

        $rows = [];

        foreach ($employees as $employee) {
            $rows[] = [
                'title' => $employee->nameForLocale(app()->getLocale()),
                'context' => implode(' · ', array_filter([
                    $employee->is_active ? __('hr_search.employees.active') : __('hr_search.employees.former'),
                    City::nameOf($employee->city_id),
                ], fn (string $part): bool => $part !== '')),
                'href' => route('hr.employees.show', ['employee' => $employee->username]),
            ];
        }

        return $rows;
    }

    /**
     * EmployeePolicy::viewDirectory as a where clause.
     *
     * @param  Builder<Employee>  $query
     */
    private function visibleTo(Builder $query, User $user): void
    {
        $cityIds = HrAccess::cityIds($user);
        $isHr = HrAccess::isHr($user);

        if (HrAccess::isGeneralManagerOrSuperAdmin($user) || ($isHr && $cityIds === null)) {
            return;
        }

        $query->where(function (Builder $visible) use ($user, $isHr, $cityIds): void {
            $visible->where('employees.is_active', true);

            if ($user->employee_id !== null) {
                $visible->orWhere('employees.id', (int) $user->employee_id);
            }

            if ($isHr) {
                $visible->orWhereIn('employees.city_id', $cityIds);
            }
        });
    }
}
