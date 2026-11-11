<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * Checklist rows are read by their own people (the employee, the deliverer,
 * the receiver) through the workflow's own scoped queries; this policy covers
 * the page gates and HR's management of one employee's checklist, which the
 * old app tied to EmployeePolicy::update (employees.update in the employee's
 * governorate).
 */
class ChecklistPolicy
{
    public function viewOwn(User $user): bool
    {
        return $user->can('checklists.view-own');
    }

    public function deliver(User $user): bool
    {
        return $user->can('checklists.deliver');
    }

    public function receive(User $user): bool
    {
        return $user->can('checklists.receive');
    }

    public function viewDone(User $user): bool
    {
        return $user->can('checklists.manage');
    }

    /**
     * Assign, change, start the return of, or close the items of one employee.
     */
    public function manage(User $user, Employee $employee): bool
    {
        if (! $user->can('employees.update')) {
            return false;
        }

        $cityIds = HrAccess::for($user)->cityIds();

        return $cityIds === null || in_array((int) $employee->city_id, $cityIds, true);
    }
}
