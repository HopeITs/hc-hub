<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Query\Builder;

/**
 * What an account needs to know about HR employees, without Core depending on
 * HR (D10). HrServiceProvider binds the implementation; User reads its
 * employee through it (photo, department, name search).
 */
interface EmployeeLookup
{
    /**
     * The URL of the employee's photo, or HR's default picture without one.
     */
    public function avatarUrl(?int $employeeId): string;

    /**
     * The URL of the employee's own photo, or null when HR holds none.
     */
    public function photoUrl(?int $employeeId): ?string;

    /**
     * The employees holding an active contract on a position of the department.
     *
     * @return list<int>
     */
    public function idsInDepartment(int $departmentId): array;

    /**
     * A subquery of the ids of employees whose English or Arabic name holds
     * the word (on the typed keyboard layout only), for whereIn().
     */
    public function idsNamedLike(string $word): Builder;
}
