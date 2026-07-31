<?php

namespace App\Observers;

use App\Models\Employee;
use App\Services\AccessRevoker;
use App\Services\DigitalIdManager;

/**
 * Ties access revocation to the employee's active flag.
 *
 * Deactivation happens from several places — the employee edit form, the
 * `deactivate:employee` scheduled command, contract termination — and each one
 * would otherwise have to remember to terminate sessions. Hooking the model
 * event means there is exactly one place to get right, and any future
 * deactivation path inherits it.
 */
class EmployeeObserver
{
    public function updated(Employee $employee): void
    {
        // Only the active -> inactive transition, and only when that column
        // actually changed in this save.
        if (! $employee->wasChanged('is_active') || $employee->is_active) {
            return;
        }

        // The printed ID card is a bearer credential; deactivation must
        // invalidate it too, not only the login.
        app(DigitalIdManager::class)->disable($employee);

        $user = $employee->account;

        if (! $user) {
            return;
        }

        app(AccessRevoker::class)->revoke($user, 'employee_deactivated');
    }
}
