<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function view(User $user, Employee $employee): bool
    {
        return $this->isSelf($user, $employee)
            || ($user->hasActivePermission('employees.view') && $this->isInActiveCity($user, $employee));
    }

    /**
     * The photo itself is not sensitive like the rest of an employee record —
     * every signed-in user may see every colleague's face. Only the HR-city
     * boundary still applies (an HR-ALP user still can't pull a Homs photo);
     * roles with no city scope (employee, manager, general-manager, ...) see
     * everyone.
     */
    public function viewAvatar(User $user, Employee $employee): bool
    {
        return $this->isSelf($user, $employee) || $this->isInActiveCity($user, $employee);
    }

    public function create(User $user, int $cityId): bool
    {
        return $user->hasActivePermission('employees.create')
            && ($user->activeCityId() === null || $user->activeCityId() === $cityId);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasActivePermission('employees.update')
            && $this->isInActiveCity($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasActivePermission('employees.delete')
            && $this->isInActiveCity($user, $employee);
    }

    public function viewPrivateDocuments(User $user, Employee $employee): bool
    {
        return $this->isSelf($user, $employee)
            || ($user->hasActivePermission('employees.update') && $this->isInActiveCity($user, $employee));
    }

    private function isSelf(User $user, Employee $employee): bool
    {
        return (int) $user->employee_id === (int) $employee->id;
    }

    private function isInActiveCity(User $user, Employee $employee): bool
    {
        $activeCityId = $user->activeCityId();

        return $activeCityId === null || (int) $employee->city_id === $activeCityId;
    }
}
