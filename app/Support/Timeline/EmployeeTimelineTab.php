<?php

declare(strict_types=1);

namespace App\Support\Timeline;

use App\Support\Modules\Contracts\EmployeeTabSource;
use Modules\Core\Models\User;

/**
 * The "Timeline" tab of an employee's profile (roadmap 4.7): shown to whoever
 * may read at least one of the timeline's sources about that person.
 */
final class EmployeeTimelineTab implements EmployeeTabSource
{
    public function __construct(private readonly EmployeeTimeline $timeline) {}

    public function key(): string
    {
        return 'timeline';
    }

    public function label(): string
    {
        return __('timeline.tab');
    }

    public function visibleTo(User $viewer, int $employeeId): bool
    {
        return $this->timeline->sourcesFor($viewer, $employeeId) !== [];
    }

    public function component(): string
    {
        return 'employees.timeline';
    }
}
