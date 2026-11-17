<?php

namespace Database\Factories\Concerns;

use Modules\Core\Models\City;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;

/**
 * Creates the HR-owned rows (governorates, departments, employees) a
 * tracking factory points at. They live in the same database, so these are
 * plain model factories; the helpers only return the new id.
 */
trait CreatesHrRecords
{
    /**
     * Create a governorate and return its id.
     */
    protected function createHrCity(): int
    {
        return City::factory()->create()->id;
    }

    /**
     * Create a department and return its id.
     */
    protected function createHrDepartment(): int
    {
        return Department::factory()->create()->id;
    }

    /**
     * Create an employee and return its id.
     */
    protected function createHrEmployee(): int
    {
        return Employee::factory()->create()->id;
    }

    /**
     * Definition entries for an employee column in the middle of a rename
     * (roadmap 1.9): both the old and the new column get the same employee.
     * A caller passing a plain id under either name sets both; otherwise one
     * employee is created and shared. Both are written because seeders run
     * without model events, and so without the model's own mirroring.
     *
     * Laravel hands each attribute closure the unresolved definition, so the
     * two closures share the id through $employeeId rather than reading each
     * other.
     *
     * @return array<string, callable(array<string, mixed>): int>
     */
    protected function renamedEmployeeColumns(string $old, string $new): array
    {
        $employeeId = null;

        $resolve = function (array $attributes) use (&$employeeId, $old, $new): int {
            $given = fn (string $key): ?int => is_numeric($attributes[$key] ?? null) ? (int) $attributes[$key] : null;

            return $employeeId ??= $given($new) ?? $given($old) ?? $this->createHrEmployee();
        };

        return [$new => $resolve, $old => $resolve];
    }
}
