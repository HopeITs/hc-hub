<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Illuminate\Auth\Access\Response;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * Who may see and change an employee record. A regional HR role (HR-ALP) is
 * limited to its cities; everyone may open their own record.
 */
class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('employees.view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->isSelf($user, $employee)
            || ($user->can('employees.view') && $this->isInActiveCity($user, $employee));
    }

    /**
     * The profile page: an active colleague is open to all, a former one only
     * to HR in their city, the general manager and the super admin.
     */
    public function viewDirectory(User $user, Employee $employee): Response
    {
        $visible = $this->isSelf($user, $employee)
            || $employee->is_active
            || $this->seesFormerEverywhere($user)
            || (HrAccess::is($user, 'HR') && $this->isInActiveCity($user, $employee));

        return $visible ? Response::allow() : Response::denyAsNotFound();
    }

    /**
     * The record tabs (education, experience, courses, contract history).
     */
    public function viewRecords(User $user, Employee $employee): bool
    {
        if ($this->viewDirectory($user, $employee)->denied()) {
            return false;
        }

        if ($this->isSelf($user, $employee) || HrAccess::is($user, 'general-manager', 'super-admin')) {
            return true;
        }

        if (HrAccess::is($user, 'HR')) {
            return $this->isInActiveCity($user, $employee);
        }

        return HrAccess::is($user, 'manager') && $this->isDirectReport($user, $employee);
    }

    public function create(User $user, ?int $cityId = null): bool
    {
        return $user->can('employees.create')
            && ($cityId === null || HrAccess::canAccessCity($user, $cityId));
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.update') && $this->isInActiveCity($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('employees.delete') && $this->isInActiveCity($user, $employee);
    }

    /**
     * Relatives, personal contact details and uploaded documents.
     */
    public function viewPrivateDocuments(User $user, Employee $employee): bool
    {
        return $this->isSelf($user, $employee)
            || ($user->can('employees.update') && $this->isInActiveCity($user, $employee));
    }

    /**
     * Reissue, disable or enable the employee's ID card.
     */
    public function manageCard(User $user, Employee $employee): bool
    {
        return HrAccess::is($user, 'super-admin')
            || (HrAccess::is($user, 'HR') && $this->isInActiveCity($user, $employee));
    }

    public function reportCardLost(User $user, Employee $employee): bool
    {
        return $this->isSelf($user, $employee) && (bool) $employee->is_active;
    }

    public function viewCard(User $user, Employee $employee): bool
    {
        return $this->manageCard($user, $employee) || $this->isSelf($user, $employee);
    }

    private function isSelf(User $user, Employee $employee): bool
    {
        return $user->employee_id !== null && (int) $user->employee_id === (int) $employee->getKey();
    }

    private function isInActiveCity(User $user, Employee $employee): bool
    {
        return HrAccess::canAccessCity($user, $employee->city_id);
    }

    private function seesFormerEverywhere(User $user): bool
    {
        return HrAccess::is($user, 'general-manager', 'super-admin')
            || (HrAccess::is($user, 'HR') && HrAccess::cityIds($user) === null);
    }

    private function isDirectReport(User $user, Employee $employee): bool
    {
        if (! $user->employee_id) {
            return false;
        }

        $managedPositionIds = Contract::query()
            ->activeNow()
            ->where('employee_id', $user->employee_id)
            ->pluck('position_id')
            ->all();

        if ($managedPositionIds === []) {
            return false;
        }

        return Contract::query()
            ->activeNow()
            ->where('employee_id', $employee->getKey())
            ->whereHas('position', fn ($position) => $position->whereIn('direct_manager_id', $managedPositionIds))
            ->exists();
    }
}
