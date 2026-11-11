<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Checklists;

use Illuminate\Support\Facades\Gate;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\EmployeeItem;

/**
 * Remove one personal item from an employee's record.
 */
final class DeleteEmployeeItem
{
    public function handle(Employee $employee, int $itemId): void
    {
        Gate::authorize('manage', [EmployeeItem::class, $employee]);

        EmployeeItem::query()->where('employee_id', $employee->id)->findOrFail($itemId)->delete();
    }
}
