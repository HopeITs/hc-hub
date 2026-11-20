<?php

declare(strict_types=1);

namespace Modules\Tracking\Support;

use App\Support\Modules\Contracts\TimelineSource;
use Carbon\CarbonImmutable;
use Modules\Core\Models\User;
use Modules\Tracking\Models\ProjectMember;

/**
 * Project memberships on an employee's timeline: when they joined a project
 * and when they left it, read from project_members. A membership whose start
 * was never recorded (an inferred row) is listed without a date. Seen by the
 * same people as the profile's Projects tab.
 */
final class ProjectMembersTimelineSource implements TimelineSource
{
    public function key(): string
    {
        return 'projects';
    }

    public function label(): string
    {
        return __('timeline.sources.projects');
    }

    public function icon(): string
    {
        return 'folder';
    }

    public function allows(User $viewer, int $employeeId): bool
    {
        return app(EmployeeProjectsTab::class)->visibleTo($viewer, $employeeId);
    }

    public function entries(User $viewer, int $employeeId): array
    {
        $entries = [];

        foreach (ProjectMember::query()->where('employee_id', $employeeId)->with('project:id,name,slug')->get() as $membership) {
            if ($membership->project === null) {
                continue;
            }

            $detail = $membership->project->name.' · '.$membership->role->label();
            $href = route('projects.show', $membership->project);

            $entries[] = [
                'date' => $membership->starts_on === null ? null : CarbonImmutable::parse($membership->starts_on),
                'title' => __('timeline.projects.joined'),
                'detail' => $detail,
                'href' => $href,
            ];

            if ($membership->ends_on !== null) {
                $entries[] = ['date' => CarbonImmutable::parse($membership->ends_on), 'title' => __('timeline.projects.left'), 'detail' => $detail, 'href' => $href];
            }
        }

        return $entries;
    }
}
