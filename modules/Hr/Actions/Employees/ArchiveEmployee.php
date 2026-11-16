<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Employees;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\Hr\Models\Employee;

/**
 * Archive an employee who has left: the record, contracts, relatives,
 * journeys, notes and every row pointing at them stay, nothing is deleted
 * (D2). The employee becomes inactive, so their login loses access and their
 * ID card is switched off, as deactivating does. It takes the same right as
 * deleting, since it is what HR does instead of deleting.
 */
class ArchiveEmployee
{
    /**
     * @return bool false when the employee was already archived
     */
    public function handle(Employee $employee): bool
    {
        Gate::authorize('delete', $employee);

        if ($employee->isArchived()) {
            return false;
        }

        $employee->forceFill([
            'archived_at' => now(),
            'archived_by' => Auth::id(),
            'is_active' => false,
        ]);

        if ($employee->digital_id_enabled) {
            $employee->forceFill([
                'digital_id_enabled' => false,
                'digital_id_revoked_at' => now(),
            ]);
        }

        $employee->save();
        Employee::forgetPickerOptions();

        return true;
    }
}
