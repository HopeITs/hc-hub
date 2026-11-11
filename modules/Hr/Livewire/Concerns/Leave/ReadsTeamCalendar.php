<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Leave;

use Carbon\CarbonImmutable;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Support\Leave\HolidayProjects;
use Modules\Hr\Support\Leave\LeaveRequestPricing;
use Throwable;

/**
 * The dates a team-calendar view reads and how the month grid lays out its
 * absence bars. Weeks start on Sunday, so the Friday–Saturday weekend
 * closes each row, as in the old app.
 */
trait ReadsTeamCalendar
{
    public const array CALENDAR_VIEWS = ['month', 'week', 'day', 'agenda'];

    public const array CALENDAR_STATUSES = ['all', 'accepted', 'pending'];

    /**
     * The Sunday–Saturday week holding $day.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function weekOf(CarbonImmutable $day): array
    {
        $start = $day->startOfDay()->subDays($day->dayOfWeek);

        return [$start, $start->addDays(6)];
    }

    /**
     * The month grid as week rows, each absence laid out once per week as a
     * bar across its days instead of a chip per day. Bars take the first
     * free lane, longer ones first; what does not fit in $lanes is counted
     * per day as "+N more".
     *
     * @param  array<string, array<string, mixed>>  $days  TeamCoverage::coverage() output
     * @return list<array{days: list<array<string, mixed>>, bars: list<array<string, mixed>>, lanes: int, hidden: array<int, int>}>
     */
    public static function monthWeeks(array $days, int $lanes = 3): array
    {
        $weeks = [];

        foreach (array_chunk(array_values($days), 7) as $week) {
            $dates = array_column($week, 'date');
            $entries = [];

            foreach ($week as $col => $day) {
                foreach ($day['members'] as $entry) {
                    $entries[$entry['key']] ??= ['entry' => $entry, 'start' => $col, 'end' => $col];
                    $entries[$entry['key']]['end'] = $col;
                }
            }

            uasort($entries, fn (array $a, array $b): int => [$a['start'], $b['end'] - $b['start'], $a['entry']['name']]
                <=> [$b['start'], $a['end'] - $a['start'], $b['entry']['name']]);

            $laneEnds = [];
            $bars = [];
            $hidden = array_fill(0, count($week), 0);

            foreach ($entries as $segment) {
                $lane = 0;

                while (isset($laneEnds[$lane]) && $laneEnds[$lane] >= $segment['start']) {
                    $lane++;
                }

                $laneEnds[$lane] = $segment['end'];

                if ($lane >= $lanes) {
                    for ($col = $segment['start']; $col <= $segment['end']; $col++) {
                        $hidden[$col]++;
                    }

                    continue;
                }

                $bars[] = [
                    'entry' => $segment['entry'],
                    'col' => $segment['start'] + 1,
                    'span' => $segment['end'] - $segment['start'] + 1,
                    'lane' => $lane,
                    'continues_before' => $segment['entry']['from'] < $dates[$segment['start']],
                    'continues_after' => $segment['entry']['to'] > $dates[$segment['end']],
                ];
            }

            $weeks[] = [
                'days' => $week,
                'bars' => $bars,
                'lanes' => min($lanes, count($laneEnds)),
                'hidden' => $hidden,
            ];
        }

        return $weeks;
    }

    protected function calendarView(): string
    {
        return in_array($this->view, self::CALENDAR_VIEWS, true) ? $this->view : 'month';
    }

    protected function anchor(): CarbonImmutable
    {
        try {
            return $this->date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->date) === 1
                ? CarbonImmutable::createFromFormat('!Y-m-d', $this->date)
                : CarbonImmutable::today();
        } catch (Throwable) {
            return CarbonImmutable::today();
        }
    }

    /**
     * The dates a view reads: the month's six-week grid (42 days), a week, a
     * day, or the month as a list.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function range(string $view): array
    {
        $anchor = $this->anchor();
        $gridStart = self::weekOf($anchor->startOfMonth())[0];

        return match ($view) {
            'week' => self::weekOf($anchor),
            'day' => [$anchor, $anchor],
            'agenda' => [$anchor->startOfMonth(), $anchor->endOfMonth()->startOfDay()],
            default => [$gridStart, $gridStart->addDays(41)],
        };
    }

    /**
     * Holiday names per day: the viewer's own (national + their project), and,
     * for someone who sets holidays, the ones they look after, so a holiday
     * added from this page shows up on it.
     *
     * @return array<string, list<array{id: int, name: string, editable: bool, national: bool}>>
     */
    protected function holidayLabels(User $user, CarbonImmutable $from, CarbonImmutable $to, HolidayProjects $projects, bool $canManage): array
    {
        $pricing = app(LeaveRequestPricing::class);
        $contract = $user->employee_id === null ? null : Contract::query()->activeNow()
            ->where('employee_id', $user->employee_id)
            ->with('position.center')
            ->orderByDesc('start_date')
            ->first();
        $position = $contract?->position;
        $cityId = $position?->center?->city_id ?? $position?->city_id;

        $byDate = $pricing->holidaysOn($position?->program_id ? (int) $position->program_id : null, $cityId !== null ? (int) $cityId : null, $from, $to);

        if ($canManage) {
            $managed = $projects->holidaysOf($user)->occurringBetween($from, $to)->get();

            foreach ($pricing->spread($managed, $from, $to) as $date => $rows) {
                $byDate[$date] = [...($byDate[$date] ?? []), ...$rows];
            }
        }

        $labels = [];

        foreach ($byDate as $date => $rows) {
            $labels[$date] = collect($rows)
                ->unique('id')
                ->map(fn (Holiday $holiday): array => [
                    'id' => (int) $holiday->id,
                    'name' => (string) $holiday->name,
                    'national' => $holiday->isNational(),
                    'editable' => $canManage && $projects->canChange($user, $holiday),
                ])
                ->values()
                ->all();
        }

        return $labels;
    }
}
