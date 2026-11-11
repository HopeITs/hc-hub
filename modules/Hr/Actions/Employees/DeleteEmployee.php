<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Employees;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Hr\Models\Employee;

/**
 * Delete an employee record. A draft is discarded with its placeholder
 * contracts; a record other rows still point at (contracts, a login) stays.
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
