<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Employees;

use Illuminate\Support\Facades\Gate;
use Modules\Hr\Models\Employee;

/**
 * Activate or deactivate an employee by hand. A draft, and a complete record
 * still waiting for its first contract, cannot be toggled: saving the
 * contract is what activates them.
 *
 * Deactivating switches the ID card off, as the HR app did. Sign-in is
 * refused for an inactive employee by the hub itself.
 */
class ToggleEmployeeStatus
{
    public function handle(Employee $employee): Employee
    {
        Gate::authorize('update', $employee);
        abort_if($employee->isDraft() || $employee->isAwaitingContract(), 403);

        $employee->is_active = ! $employee->is_active;

        if (! $employee->is_active && $employee->digital_id_enabled) {
            $employee->forceFill([
                'digital_id_enabled' => false,
                'digital_id_revoked_at' => now(),
            ]);
        }

        $employee->save();
        Employee::forgetPickerOptions();

        return $employee;
    }
}
