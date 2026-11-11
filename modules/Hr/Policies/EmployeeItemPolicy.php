<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;

/**
 * An employee's personal items (laptop brand, serial...) are part of the
 * record HR edits, so they follow the same rule as their checklist.
 */
class EmployeeItemPolicy
{
    public function manage(User $user, Employee $employee): bool
    {
        return (new ChecklistPolicy)->manage($user, $employee);
    }
}
