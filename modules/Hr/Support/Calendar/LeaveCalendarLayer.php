<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Calendar;

use App\Support\Modules\Contracts\CalendarLayerSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\User;
use Modules\Hr\Support\Desk\HrViewer;
use Modules\Hr\Support\Desk\TeamLeaves;

/**
 * The leave layer of the calendar: the accepted leave of the viewer and of
 * their team (HrViewer, the same people the HR Desk cards are about), on
 * every day it covers. Open to whoever may open the team calendar.
 */
final class LeaveCalendarLayer implements CalendarLayerSource
{
    public function key(): string
    {
        return 'leave';
    }

    public function label(): string
    {
        return __('hr_leave.calendar.layers.leave');
    }

    public function icon(): string
    {
        return 'user-minus';
    }

    public function leads(): bool
    {
        return false;
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null && $user->canAny(['leaves.view-own', 'leaves.view-all']);
    }

    /**
     * @return array<string, list<array{id: string, title: string, context: string|null, href: string|null}>>
     */
    public function between(User $user, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $from = $from->startOfDay();
        $to = $to->startOfDay();
        $href = Route::has('hr.leave.team-calendar') ? route('hr.leave.team-calendar') : null;
        $days = [];

        foreach (app(TeamLeaves::class)->between(HrViewer::for($user)->teamIds(), $from, $to) as $leave) {
            $employee = TeamLeaves::employeeOf($leave);
            $leaveType = $leave->leaveBalance?->leaveType;
            $item = [
                'id' => 'leave-'.$leave->id,
                'title' => $employee === null ? '—' : (string) $employee->name,
                'context' => $leaveType === null ? null : (string) $leaveType->name,
                'href' => $href,
            ];

            $day = CarbonImmutable::instance($leave->start_at)->startOfDay()->max($from);
            $last = CarbonImmutable::instance($leave->end_at)->startOfDay()->min($to);

            for (; $day->lte($last); $day = $day->addDay()) {
                $days[$day->toDateString()][] = $item;
            }
        }

        ksort($days);

        return $days;
    }
}
