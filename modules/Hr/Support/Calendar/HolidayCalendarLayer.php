<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Calendar;

use App\Support\Modules\Contracts\CalendarLayerSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\User;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Support\Desk\HrViewer;
use Modules\Hr\Support\Leave\LeaveRequestPricing;

/**
 * The holidays layer of the calendar: the national holidays and the ones of
 * the viewer's own program and governorate, the same days their leave is
 * priced around. Someone who sets holidays gets a link to the holidays page.
 */
final class HolidayCalendarLayer implements CalendarLayerSource
{
    public function key(): string
    {
        return 'holiday';
    }

    public function label(): string
    {
        return __('hr_leave.calendar.layers.holiday');
    }

    public function icon(): string
    {
        return 'sun';
    }

    public function leads(): bool
    {
        return true;
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null;
    }

    /**
     * @return array<string, list<array{id: string, title: string, context: string|null, href: string|null}>>
     */
    public function between(User $user, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $viewer = HrViewer::for($user);
        $href = Route::has('hr.leave.holidays') && $user->can('project-basics.view') ? route('hr.leave.holidays') : null;
        $days = [];

        foreach (app(LeaveRequestPricing::class)->holidaysOn($viewer->programId(), $viewer->cityId(), $from, $to) as $date => $holidays) {
            $days[$date] = array_values(collect($holidays)
                ->unique('id')
                ->map(fn (Holiday $holiday): array => [
                    'id' => 'holiday-'.$holiday->id,
                    'title' => (string) $holiday->name,
                    'context' => null,
                    'href' => $href,
                ])
                ->all());
        }

        return $days;
    }
}
