<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Inbox;

use App\Support\Modules\Contracts\DeskAgendaSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\User;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Desk\HrViewer;
use Modules\Hr\Support\Desk\TeamLeaves;
use Modules\Hr\Support\Desk\UpcomingHolidays;

/**
 * The HR rows of the Desk's agenda: the holidays that apply to the viewer's
 * program and governorate, and the approved leave of the people in their team
 * (the same reads the HR calendar card makes). Always today or later; a past
 * day is never overdue, so a window that ends before today gets nothing.
 */
final class DeskAgenda implements DeskAgendaSource
{
    /**
     * The furthest day ahead holidays are read.
     */
    private const int HolidayDays = 60;

    /**
     * @return Collection<int, array{key: string, kind: string, date: CarbonImmutable, title: string, context: string, project: string|null, href: string}>
     */
    public function between(User $user, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $today = CarbonImmutable::today();

        if ($user->employee_id === null || $to->lt($today)) {
            return new Collection;
        }

        $viewer = HrViewer::for($user);
        $from = $from->lt($today) ? $today : $from;

        $holidays = app(UpcomingHolidays::class)->for($viewer->programId(), $viewer->cityId(), self::HolidayDays)
            ->filter(fn (array $holiday): bool => $holiday['from']->lte($to))
            ->map(fn (array $holiday): array => [
                'key' => 'holiday-'.$holiday['id'].'-'.$holiday['from']->toDateString(),
                'kind' => 'holiday',
                'date' => $holiday['from']->lt($from) ? $from : $holiday['from'],
                'title' => $holiday['name'],
                'context' => $holiday['length'] > 1 ? $holiday['from']->translatedFormat('j M').' – '.$holiday['to']->translatedFormat('j M') : $holiday['from']->translatedFormat('j M'),
                'project' => null,
                'href' => $this->link('hr.leave.holidays'),
            ]);

        $leaves = app(TeamLeaves::class)->between($viewer->othersIds(), $from, $to)
            ->map(function (LeaveRequest $leave) use ($from): array {
                $start = CarbonImmutable::instance($leave->start_at)->startOfDay();

                return [
                    'key' => 'team-leave-'.$leave->id,
                    'kind' => 'team-leave',
                    'date' => $start->lt($from) ? $from : $start,
                    'title' => (string) (TeamLeaves::employeeOf($leave)?->name ?? '—'),
                    'context' => (string) ($leave->leaveBalance?->leaveType?->name ?? ''),
                    'project' => null,
                    'href' => $this->link('hr.leave.team-calendar'),
                ];
            });

        return $holidays->concat($leaves)->filter(fn (array $item): bool => $item['href'] !== '')->values();
    }

    private function link(string $route): string
    {
        return Route::has($route) ? route($route) : '';
    }
}
