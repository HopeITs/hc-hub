<?php

declare(strict_types=1);

namespace Modules\Hr\Observers;

use Modules\Hr\Support\Desk\HrStatistics;

/**
 * A contract or an employee saved or deleted moves the HR figures on, so
 * the Desk cards and the statistics pages show the change in their next read
 * instead of after their ten minutes (HrStatistics::flush).
 */
final class HrStatisticsObserver
{
    public function saved(): void
    {
        HrStatistics::flush();
    }

    public function deleted(): void
    {
        HrStatistics::flush();
    }
}
