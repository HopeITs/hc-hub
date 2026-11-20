<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Metrics;

use App\Support\Modules\Contracts\MetricSource;
use Modules\Hr\Support\Desk\HrStatistics;

/**
 * The organisational numbers HR owns (roadmap 4.12), read through
 * App\Support\Metrics\OrgMetrics. Their one definition stays where it has
 * always been, HrStatistics::totals() (remembered ten minutes, refreshed by
 * every saved contract or employee): the HR Desk cards and the statistics
 * page read that same method, so these keys add a door, not a second count.
 */
final class HrMetrics implements MetricSource
{
    public const string ActiveEmployees = 'active_employees';

    public const string EmployeesOnRecord = 'employees_on_record';

    public const string RunningContracts = 'running_contracts';

    public const string ContractsEndingSoon = 'contracts_ending_soon';

    public function __construct(private readonly HrStatistics $statistics) {}

    public function metrics(): array
    {
        return [
            self::ActiveEmployees => ['label' => __('hr_statistics.totals.active'), 'resolve' => $this->activeEmployees(...)],
            self::EmployeesOnRecord => ['label' => __('hr_statistics.totals.employees'), 'resolve' => $this->employeesOnRecord(...)],
            self::RunningContracts => ['label' => __('hr_statistics.totals.contracts'), 'resolve' => $this->runningContracts(...)],
            self::ContractsEndingSoon => ['label' => __('hr_statistics.totals.ending'), 'resolve' => $this->contractsEndingSoon(...)],
        ];
    }

    /**
     * Employees whose record is active.
     *
     * @param  list<int>|null  $cities
     */
    private function activeEmployees(?array $cities): int
    {
        return $this->statistics->totals($cities)['active'];
    }

    /**
     * Every employee record, active or not.
     *
     * @param  list<int>|null  $cities
     */
    private function employeesOnRecord(?array $cities): int
    {
        return $this->statistics->totals($cities)['employees'];
    }

    /**
     * Contracts running today.
     *
     * @param  list<int>|null  $cities
     */
    private function runningContracts(?array $cities): int
    {
        return $this->statistics->totals($cities)['contracts'];
    }

    /**
     * Running contracts that end within thirty days.
     *
     * @param  list<int>|null  $cities
     */
    private function contractsEndingSoon(?array $cities): int
    {
        return $this->statistics->totals($cities)['ending'];
    }
}
