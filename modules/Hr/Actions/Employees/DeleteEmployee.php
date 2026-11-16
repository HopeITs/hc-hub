<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Employees;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Hr\Models\Employee;

/**
 * Delete an employee record. A draft is discarded with its placeholder
 * contracts. Any other record with a contract or a login stays: deleting it
 * would cascade away its contracts, relatives, journeys and notes, and would
 * leave its login with no employee, which the hub treats as an always-active
 * system account. Such a person is deactivated instead. Rows that still point
 * at the employee from Tracking or recruitment block the delete as well
 * (restrict foreign keys).
 */
class DeleteEmployee
{
    /**
     * @return bool false when the record is still referenced and nothing changed
     */
    public function handle(Employee $employee, bool $discardDraft = false): bool
    {
        Gate::authorize($discardDraft ? 'update' : 'delete', $employee);

        if ($discardDraft) {
            abort_unless($employee->isDraft(), 403);
            abort_if($employee->realContracts()->exists() || $employee->account()->exists(), 403);
        } elseif ($employee->allContracts()->exists() || $employee->account()->exists()) {
            return false;
        }

        try {
            DB::transaction(function () use ($employee, $discardDraft): void {
                if ($discardDraft) {
                    $employee->allContracts()->get()->each->delete();
                }

                $employee->delete();
            });
        } catch (QueryException) {
            return false;
        }

        Employee::forgetPickerOptions();

        return true;
    }
}
