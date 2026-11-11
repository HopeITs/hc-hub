<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Modules\Hr\Support\Desk\HrStatistics;

/**
 * Employees by department: each person counted once, on their newest contract.
 */
final class DepartmentsCard extends HeadcountCard
{
    public function key(): string
    {
        return 'departments';
    }

    protected function split(HrStatistics $statistics, ?array $cities): array
    {
        return $statistics->byDepartment($cities);
    }
}
