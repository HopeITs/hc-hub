<?php

declare(strict_types=1);

namespace Modules\Tracking\Support\Metrics;

use App\Support\EmployeeLookup;
use App\Support\Modules\Contracts\MetricSource;
use Illuminate\Database\Eloquent\Builder;
use Modules\Tracking\Enums\ProjectStatus;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;

/**
 * The organisational numbers Tracking owns (roadmap 4.12), read through
 * App\Support\Metrics\OrgMetrics and defined nowhere else.
 */
final class TrackingMetrics implements MetricSource
{
    public const string EmployeesInProjects = 'employees_in_projects';

    public const string RunningProjects = 'running_projects';

    public function __construct(private readonly EmployeeLookup $employees) {}

    public function metrics(): array
    {
        return [
            self::EmployeesInProjects => [
                'label' => __('org_metrics.employees_in_projects'),
                'resolve' => $this->employeesInProjects(...),
            ],
            self::RunningProjects => [
                'label' => __('org_metrics.running_projects'),
                'resolve' => $this->runningProjects(...),
            ],
        ];
    }

    /**
     * Employees on a project team today (02-target-design §9: open
     * project_members rows ∩ active employee).
     *
     * Counted: distinct employees, so a person on two projects is one. Only
     * memberships still open (ends_on is null); an ended one is history. Only
     * employees whose HR record is active (EmployeeLookup::activeIdsIn, the
     * same "active" as HR's head count), so a former or archived employee
     * whose membership nobody ended is not counted and the number can never
     * be larger than "Active employees" beside it. Every membership role
     * counts (owner, member, …), whatever way the row came about.
     *
     * Not looked at: the project's status. A membership left open on a closed
     * project still counts until someone ends it, because project_members is
     * the record of who is on a team and closing a project does not end them.
     *
     * The governorates narrow by where the employee lives (employees.city_id,
     * as every HR head count does), not by where the project runs.
     *
     * @param  list<int>|null  $cities
     */
    private function employeesInProjects(?array $cities): int
    {
        return ProjectMember::query()
            ->current()
            ->whereIn('project_members.employee_id', $this->employees->activeIdsIn($cities))
            ->distinct()
            ->count('project_members.employee_id');
    }

    /**
     * Projects being delivered now: status Active, whatever their dates. In
     * given governorates, the projects that run there (projects.city_id).
     *
     * @param  list<int>|null  $cities
     */
    private function runningProjects(?array $cities): int
    {
        return Project::query()
            ->where('status', ProjectStatus::Active)
            ->when($cities !== null, fn (Builder $query) => $query->whereIn('city_id', $cities))
            ->count();
    }
}
