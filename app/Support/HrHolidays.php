<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Models\HolidayProject;
use Modules\Tracking\Models\Project;

/**
 * The holidays HR records (`holidays`) that apply to a project, as a "is
 * this day a holiday?" test for WorkingCalendar::withHolidays().
 *
 * A national holiday applies to every project. A holiday scoped to projects
 * applies when one of its program × governorate pairs (`holiday_project`) is
 * the project's own program and city. The birthday holiday is left out: it is
 * a validity window, each person's day off being their own. A recurring
 * holiday comes back every year on the same month and day range.
 *
 * The tables are read once per request (two queries).
 */
final class HrHolidays
{
    /**
     * The test for the project's holidays.
     *
     * @return Closure(CarbonInterface): bool
     */
    public static function forProject(Project $project): Closure
    {
        $pair = $project->program_id.':'.$project->city_id;

        $holidays = array_values(array_filter(
            self::all(),
            fn (array $holiday): bool => $holiday['national'] || in_array($pair, $holiday['pairs'], true),
        ));

        return fn (CarbonInterface $day): bool => self::falls($day, $holidays);
    }

    /**
     * Whether the day is inside one of the holidays.
     *
     * @param  list<array{national: bool, pairs: list<string>, start: CarbonImmutable, end: CarbonImmutable, recurring: bool}>  $holidays
     */
    private static function falls(CarbonInterface $day, array $holidays): bool
    {
        $date = CarbonImmutable::parse($day)->startOfDay();

        foreach ($holidays as $holiday) {
            if (! $holiday['recurring']) {
                if ($date->betweenIncluded($holiday['start'], $holiday['end'])) {
                    return true;
                }

                continue;
            }

            // A recurring holiday: the same month and day range in the day's year.
            $start = $holiday['start']->setYear($date->year);
            $end = $holiday['end']->setYear($date->year);

            if ($end->lessThan($start)) {
                $end = $end->addYear();
            }

            if ($date->betweenIncluded($start, $end) || $date->betweenIncluded($start->subYear(), $end->subYear())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every holiday but the birthday one, reduced to what the test needs.
     *
     * @return list<array{national: bool, pairs: list<string>, start: CarbonImmutable, end: CarbonImmutable, recurring: bool}>
     */
    private static function all(): array
    {
        return once(function (): array {
            $holidays = Holiday::query()
                ->where('kind', '!=', Holiday::KIND_BIRTHDAY)
                ->with('projects:id,holiday_id,program_id,city_id')
                ->get(['id', 'start_date', 'end_date', 'is_recurring', 'scope']);

            return array_values($holidays->map(fn (Holiday $holiday): array => [
                'national' => $holiday->isNational(),
                'pairs' => array_values($holiday->projects->map(fn (HolidayProject $link): string => $link->program_id.':'.$link->city_id)->all()),
                'start' => $holiday->start_date->startOfDay(),
                'end' => $holiday->end_date->startOfDay(),
                'recurring' => $holiday->is_recurring,
            ])->all());
        });
    }
}
