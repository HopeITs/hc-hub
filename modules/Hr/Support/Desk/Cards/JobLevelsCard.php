<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Modules\Hr\Support\Desk\HrStatistics;

/**
 * Employees by job level: each person counted once, on their newest contract.
 */
final class JobLevelsCard extends HeadcountCard
{
    public function key(): string
    {
        return 'job-levels';
    }

    protected function split(HrStatistics $statistics, ?array $cities): array
    {
        return $statistics->byJobLevel($cities);
    }
}
