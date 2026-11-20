<?php

declare(strict_types=1);

namespace App\Support\Timeline;

use App\Models\Activity;
use App\Notifications\ActivityPresenter;
use App\Support\Modules\Contracts\TimelineSource;
use Carbon\CarbonImmutable;
use Modules\Core\Models\User;

/**
 * What the activity log says about an employee (activities.employee_id),
 * for readers of the activity log. The log is pruned after some years, so
 * the timeline never depends on it: the lasting facts come from the other
 * sources, and this one only adds the recent detail while it exists.
 */
final class ActivityTimelineSource implements TimelineSource
{
    /** The most log entries one timeline shows. */
    public const int Limit = 50;

    public function __construct(private readonly ActivityPresenter $presenter) {}

    public function key(): string
    {
        return 'activity';
    }

    public function label(): string
    {
        return __('timeline.sources.activity');
    }

    public function icon(): string
    {
        return 'clock';
    }

    public function allows(User $viewer, int $employeeId): bool
    {
        return $viewer->can('activity-log.view');
    }

    public function entries(User $viewer, int $employeeId): array
    {
        $entries = [];

        foreach (Activity::query()->where('employee_id', $employeeId)->with('actor:id,email,employee_id')->latest('id')->limit(self::Limit)->get() as $activity) {
            $entries[] = [
                'date' => $activity->created_at === null ? null : CarbonImmutable::instance($activity->created_at),
                'title' => $this->presenter->sentence($activity),
                'detail' => null,
                'href' => null,
            ];
        }

        return $entries;
    }
}
