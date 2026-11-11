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
}
