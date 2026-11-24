<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Attendance;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Hr\Enums\ContractType;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\AttendanceDayDecision;
use Modules\Hr\Models\AttendancePunch;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\LeaveType;

/**
 * One employee's month, day by day, in the terms of the printed Monthly TimeSheet: the leave type
 * and hours, the overtime and the working days. The one place a month becomes rows; the page, the
 * print and the workflow all read it.
 *
 * Where each figure comes from:
 *  - the days the contract covers, and its daily hours (weekly hours / 5): the contract;
 *  - weekends: the center's weekend setting; holidays: the Holidays page;
 *  - leave: accepted leave requests (a full day or an hourly leave);
 *  - worked or not, and the time at work: the uploaded punches (first to last of the day);
 *  - a working day with no record, or only one punch: nothing is assumed, it waits for an answer
 *    (AttendanceDayDecision) and counts for no working day until then.
 *
 * Overtime is not paid; it is shown in hours and as free days (hours / daily hours).
 */
class Timesheet
{
    public const OUTSIDE = 'outside';

    public const WEEKEND = 'weekend';

    public const HOLIDAY = 'holiday';

    public const LEAVE = 'leave';

    public const WORKED = 'worked';

    public const ONE_PUNCH = 'one_punch';

    public const NO_RECORD = 'no_record';

    public const DECIDED_WORKED = 'decided_worked';

    public const DECIDED_ABSENT = 'decided_absent';

    /** Every kind a day can be, in the order the key shows them. */
    public const KINDS = [self::WORKED, self::DECIDED_WORKED, self::ONE_PUNCH, self::NO_RECORD, self::LEAVE, self::DECIDED_ABSENT, self::WEEKEND, self::HOLIDAY, self::OUTSIDE];

    /**
     * @return array{
     *     month: CarbonImmutable,
     *     placement: array{program_id: ?int, program: ?string, department_id: ?int, department: ?string, center: ?string, position: ?string},
     *     daily_hours: float,
     *     rows: list<array<string, mixed>>,
     *     totals: array{leave_hours: float, overtime_hours: float, overtime_days: float, working_days: float, worked_days: int, to_decide: int},
     *     balances: list<array<string, mixed>>
     * }
     */
    public function forEmployee(Employee $employee, CarbonImmutable $month): array
    {
        return $this->forEmployees(collect([$employee]), $month)[$employee->id];
    }

    /**
     * The same sheets for many people at once, in a handful of queries however many there are
     * (contracts, punches, decisions, leave, balances), instead of a set per person. Each sheet
     * is exactly what forEmployee() returns for that person.
     *
     * @param  Collection<int, Employee>  $employees
     * @return array<int, array<string, mixed>> the sheets, by employee id
     */
    public function forEmployees(Collection $employees, CarbonImmutable $month): array
    {
        $ids = array_values(array_map('intval', $employees->map(fn (Employee $employee): int => (int) $employee->id)->all()));

        if ($ids === []) {
            return [];
        }

        $first = $month->startOfMonth();
        $last = $month->endOfMonth()->startOfDay();

        $contracts = Contract::query()
            ->with('position.center', 'position.program', 'position.department')
            ->whereIn('employee_id', $ids)
            ->where(fn ($type) => $type->whereNull('type')->orWhere('type', '!=', ContractType::Draft->value))
            ->whereDate('start_date', '<=', $last->toDateString())
            ->whereDate('end_date', '>=', $first->toDateString())
            ->orderBy('start_date')
            ->get()
            ->groupBy('employee_id');

        $punches = [];

        AttendancePunch::query()
            ->whereIn('employee_id', $ids)
            ->where('punched_at', '>=', $first->startOfDay())
            ->where('punched_at', '<=', $last->endOfDay())
            ->orderBy('punched_at')
            ->get(['employee_id', 'punched_at'])
            ->each(function (AttendancePunch $punch) use (&$punches): void {
                $at = CarbonImmutable::instance($punch->punched_at);
                $punches[(int) $punch->employee_id][$at->toDateString()][] = $at;
            });

        $decisions = AttendanceDayDecision::query()
            ->whereIn('employee_id', $ids)
            ->whereDate('work_date', '>=', $first->toDateString())
            ->whereDate('work_date', '<=', $last->toDateString())
            ->get()
            ->groupBy('employee_id');

        $leaves = LeaveRequest::query()
            ->with('leaveBalance.leaveType', 'leaveBalance.contract:id,employee_id')
            ->where('status', LeaveRequestStatus::Accepted->value)
            ->whereHas('leaveBalance.contract', fn ($contract) => $contract->whereIn('employee_id', $ids))
            ->whereDate('start_at', '<=', $last->toDateString())
            ->whereDate('end_at', '>=', $first->toDateString())
            ->get()
            ->groupBy(fn (LeaveRequest $leave): int => (int) $leave->leaveBalance?->contract?->employee_id);

        // The balances page 2 shows are those of each person's latest contract in the month.
        $latestIds = $contracts->map(fn (Collection $all): ?int => $all->sortByDesc('start_date')->first()?->id)->filter();
        $balances = LeaveBalance::query()
            ->with('leaveType')
            ->whereIn('contract_id', $latestIds->values()->all())
            ->orderBy('type_id')
            ->get()
            ->groupBy('contract_id');

        $unpaid = LeaveType::query()->find(LeaveTypeCases::Unpaid);
        $holidayCache = [];
        $sheets = [];

        foreach ($employees as $employee) {
            $id = (int) $employee->id;
            $sheets[$id] = $this->build(
                $first,
                $last,
                $contracts->get($id, collect()),
                $punches[$id] ?? [],
                $decisions->get($id, collect())->keyBy(fn (AttendanceDayDecision $decision): string => $decision->work_date->toDateString()),
                $leaves->get($id, collect()),
                $unpaid,
                $holidayCache,
                $balances->get($latestIds->get($id), collect()),
            );
        }

        return $sheets;
    }

    /**
     * One person's month from what forEmployees() has already read.
     *
     * @param  Collection<int, Contract>  $contracts
     * @param  array<string, list<CarbonImmutable>>  $punches  times by day
     * @param  Collection<string, AttendanceDayDecision>  $decisions  by date
     * @param  Collection<int, LeaveRequest>  $leaves
     * @param  array<string, mixed>  $holidayCache  shared by everyone on the page
     * @param  Collection<int, LeaveBalance>  $balanceRows
     * @return array<string, mixed>
     */
    private function build(CarbonImmutable $first, CarbonImmutable $last, Collection $contracts, array $punches, Collection $decisions, Collection $leaves, ?LeaveType $unpaid, array &$holidayCache, Collection $balanceRows): array
    {
        $overtime = (array) config('attendance.overtime');
        $block = max(1, (int) $overtime['block_minutes']);

        $rows = [];

        for ($day = $first; $day->lte($last); $day = $day->addDay()) {
            $date = $day->toDateString();
            $contract = $this->coveringContract($contracts, $day);
            $times = $punches[$date] ?? [];
            $row = [
                'date' => $day,
                'kind' => self::OUTSIDE,
                'leave_type' => null,
                'leave_hours' => 0.0,
                'overtime_minutes' => 0,
                'working_days' => 0.0,
                'note' => null,
                'first' => null,
                'last' => null,
                'worked_minutes' => null,
                'punch_count' => count($times),
                'decidable' => false,
                'decision' => null,
                'paid_off_day' => false,
            ];

            if ($contract === null) {
                $rows[] = $row;

                continue;
            }

            $daily = $this->dailyHoursOf($contract);
            $workedMinutes = null;

            if (count($times) >= 2) {
                $row['first'] = $times[0];
                $row['last'] = end($times);
                $workedMinutes = (int) $times[0]->diffInMinutes(end($times), true);
                $row['worked_minutes'] = $workedMinutes;
            } elseif (count($times) === 1) {
                $row['first'] = $times[0];
            }

            $position = $contract->position;
            $key = ($position?->program_id ?? 0).'-'.($position?->city_id ?? 0);
            $holidayCache[$key] ??= $this->holidaysFor($position?->program_id, $position?->city_id, $first, $last);

            // Days picked one by one on the contract: every other day is off (the center's day plus the volunteer's own).
            $workdays = $contract->workWeekdays();
            $weekends = $workdays !== null
                ? array_values(array_diff(range(0, 6), $workdays))
                : array_map('intval', explode(',', (string) ($position?->center?->weekends ?: config('attendance.default_weekends'))));
            $isWeekend = in_array($day->dayOfWeek, $weekends, true);

            if ($isWeekend || isset($holidayCache[$key][$date])) {
                $row['kind'] = $isWeekend ? self::WEEKEND : self::HOLIDAY;
                $row['leave_hours'] = $daily;
                $row['working_days'] = 1.0;
                $row['note'] = $isWeekend ? null : $holidayCache[$key][$date];
                $row['paid_off_day'] = true;

                if ($overtime['weekend_counts_fully'] && $workedMinutes !== null) {
                    $row['overtime_minutes'] = intdiv($workedMinutes, $block) * $block;
                }

                $rows[] = $row;

                continue;
            }

            $decision = $decisions[$date] ?? null;

            if ($decision !== null) {
                $row['decidable'] = true;
                $row['decision'] = $decision->kind;
                $row['note'] = $decision->note;

                if ($decision->kind === AttendanceDayDecision::ABSENT) {
                    $row['kind'] = self::DECIDED_ABSENT;
                    $row['leave_type'] = $unpaid;
                    $row['leave_hours'] = $daily;
                } else {
                    $row['kind'] = self::DECIDED_WORKED;
                    $row['working_days'] = 1.0;
                    $row['overtime_minutes'] = $this->overtimeMinutes($workedMinutes, $daily, $overtime);
                }

                $rows[] = $row;

                continue;
            }

            [$leaveHours, $leaveType] = $this->leaveOn($leaves, $day, $daily);

            if ($leaveHours >= $daily) {
                $row['kind'] = self::LEAVE;
                $row['leave_type'] = $leaveType;
                $row['leave_hours'] = $daily;
                $rows[] = $row;

                continue;
            }

            if ($leaveHours > 0) {
                // An hourly leave inside a working day: the rest of the day is worked.
                $row['kind'] = self::WORKED;
                $row['leave_type'] = $leaveType;
                $row['leave_hours'] = $leaveHours;
                $row['working_days'] = 1 - ($leaveHours / $daily);
                $row['overtime_minutes'] = $this->overtimeMinutes($workedMinutes, $daily, $overtime);
                $rows[] = $row;

                continue;
            }

            if ($workedMinutes !== null) {
                $row['kind'] = self::WORKED;
                $row['working_days'] = 1.0;
                $row['overtime_minutes'] = $this->overtimeMinutes($workedMinutes, $daily, $overtime);
            } else {
                // One punch, or none: nothing is assumed, it waits for an answer.
                $row['kind'] = count($times) === 1 ? self::ONE_PUNCH : self::NO_RECORD;
                $row['decidable'] = true;
            }

            $rows[] = $row;
        }

        $latest = $contracts->sortByDesc('start_date')->first();
        $dailyHours = $latest instanceof Contract ? $this->dailyHoursOf($latest) : (float) config('attendance.default_daily_hours');

        return [
            'month' => $first,
            'placement' => $this->placement($latest),
            'daily_hours' => $dailyHours,
            'rows' => $rows,
            'totals' => $this->totals($rows, $dailyHours),
            'balances' => $this->balances($latest, $balanceRows, $rows, $dailyHours),
        ];
    }

    private function dailyHoursOf(Contract $contract): float
    {
        return $contract->weekly_hours ? $contract->weekly_hours / 5 : (float) config('attendance.default_daily_hours');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{leave_hours: float, overtime_hours: float, overtime_days: float, working_days: float, worked_days: int, to_decide: int}
     */
    private function totals(array $rows, float $dailyHours): array
    {
        $leave = 0.0;
        $minutes = 0;
        $days = 0.0;
        $toDecide = 0;
        $workedDays = 0;

        foreach ($rows as $row) {
            if (! $row['paid_off_day']) {
                $leave += $row['leave_hours'];
            }

            $minutes += $row['overtime_minutes'];
            $days += $row['working_days'];

            if (in_array($row['kind'], [self::ONE_PUNCH, self::NO_RECORD], true)) {
                $toDecide++;
            }

            if (in_array($row['kind'], [self::WORKED, self::DECIDED_WORKED], true)) {
                $workedDays++;
            }
        }

        $overtimeHours = $minutes / 60;

        return [
            'leave_hours' => round($leave, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'overtime_days' => $dailyHours > 0 ? round($overtimeHours / $dailyHours, 2) : 0.0,
            'working_days' => round($days, 2),
            'worked_days' => $workedDays,
            'to_decide' => $toDecide,
        ];
    }

    /**
     * @param  Collection<int, Contract>  $contracts
     */
    private function coveringContract(Collection $contracts, CarbonImmutable $day): ?Contract
    {
        return $contracts
            ->filter(function (Contract $contract) use ($day): bool {
                if ($contract->start_date === null || $contract->end_date === null) {
                    return false;
                }

                $end = $contract->terminated_at !== null && $contract->terminated_at->lt($contract->end_date)
                    ? $contract->terminated_at
                    : $contract->end_date;

                return $contract->start_date->startOfDay()->lte($day) && $end->startOfDay()->gte($day);
            })
            ->sortByDesc('start_date')
            ->first();
    }

    /**
     * Where the person works this month: the project (program), department and center of their
     * latest contract running in the month. Timesheets are grouped by it.
     *
     * @return array{program_id: ?int, program: ?string, department_id: ?int, department: ?string, center: ?string, position: ?string}
     */
    private function placement(?Contract $contract): array
    {
        $position = $contract?->position;

        return [
            'program_id' => $position?->program_id !== null ? (int) $position->program_id : null,
            'program' => $position?->program?->name,
            'department_id' => $position?->department_id !== null ? (int) $position->department_id : null,
            'department' => $position?->department?->name,
            'center' => $position?->center?->name,
            'position' => $position === null ? null : ($position->title_ar ?: $position->title_en),
        ];
    }

    /**
     * Minutes of overtime for a worked day, by the configured rule.
     *
     * @param  array<string, mixed>  $rule
     */
    private function overtimeMinutes(?int $workedMinutes, float $dailyHours, array $rule): int
    {
        if ($workedMinutes === null) {
            return 0;
        }

        $over = $workedMinutes - (int) round($dailyHours * 60);
        $block = max(1, (int) $rule['block_minutes']);

        return $over >= (int) $rule['threshold_minutes'] ? intdiv($over, $block) * $block : 0;
    }

    /**
     * Hours of accepted leave on a working day, and its type.
     *
     * @param  Collection<int, LeaveRequest>  $leaves
     * @return array{0: float, 1: ?LeaveType}
     */
    private function leaveOn(Collection $leaves, CarbonImmutable $day, float $daily): array
    {
        $hours = 0.0;
        $type = null;

        foreach ($leaves as $leave) {
            $start = CarbonImmutable::instance($leave->start_at);
            $end = CarbonImmutable::instance($leave->end_at);

            if ($leave->type === 'hour') {
                if (! $start->isSameDay($day)) {
                    continue;
                }

                $hours += $leave->computed_hours ?? ($end->diffInMinutes($start, true) / 60);
            } elseif ($start->startOfDay()->lte($day) && $end->startOfDay()->gte($day)) {
                $hours += $daily;
            } else {
                continue;
            }

            $type ??= $leave->leaveBalance?->leaveType;
        }

        return [min($hours, $daily), $type];
    }

    /**
     * The dates of the month that are holidays for a program in a city, with their Arabic names
     * (the printed form is in Arabic). Birthday holidays are personal and are left out.
     *
     * @return array<string, string>
     */
    private function holidaysFor(?int $programId, ?int $cityId, CarbonImmutable $first, CarbonImmutable $last): array
    {
        $dates = [];

        Holiday::query()
            ->where('kind', '!=', Holiday::KIND_BIRTHDAY)
            ->applicableTo($programId, $cityId)
            ->occurringBetween($first, $last)
            ->get()
            ->each(function (Holiday $holiday) use (&$dates, $first, $last): void {
                $length = max(0, (int) $holiday->start_date->diffInDays($holiday->end_date, true));
                $years = $holiday->is_recurring ? array_unique([$first->year, $last->year]) : [null];

                foreach ($years as $year) {
                    $start = $year === null
                        ? CarbonImmutable::instance($holiday->start_date)->startOfDay()
                        : CarbonImmutable::create($year, $holiday->start_date->month, $holiday->start_date->day);

                    for ($i = 0; $i <= $length; $i++) {
                        $date = $start->addDays($i);

                        if ($date->between($first, $last)) {
                            $dates[$date->toDateString()] = (string) ($holiday->name_ar ?: $holiday->name_en);
                        }
                    }
                }
            });

        return $dates;
    }

    /**
     * The leave balances of the employee's current contract, for page 2 of the form: what was
     * entitled, what was used in the month, and what is left, as the leave module holds them.
     *
     * @param  Collection<int, LeaveBalance>  $balanceRows
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function balances(?Contract $contract, Collection $balanceRows, array $rows, float $dailyHours): array
    {
        if ($contract === null || $dailyHours <= 0) {
            return [];
        }

        $usedThisMonth = [];

        foreach ($rows as $row) {
            if (! $row['paid_off_day'] && $row['leave_type'] instanceof LeaveType && $row['leave_hours'] > 0) {
                $usedThisMonth[$row['leave_type']->id] = ($usedThisMonth[$row['leave_type']->id] ?? 0) + $row['leave_hours'];
            }
        }

        return array_values($balanceRows
            ->map(function (LeaveBalance $balance) use ($usedThisMonth, $dailyHours): array {
                $entitled = (float) $balance->deserved;
                $left = $entitled - (float) $balance->used;
                $month = (float) ($usedThisMonth[$balance->type_id] ?? 0);

                return [
                    'type' => $balance->leaveType,
                    'entitled_days' => round($entitled / $dailyHours, 2),
                    'month_hours' => round($month, 2),
                    'month_days' => round($month / $dailyHours, 2),
                    'remaining_hours' => round($left, 2),
                    'remaining_days' => (int) floor(max(0, $left) / $dailyHours),
                    'remaining_rest_hours' => (int) round(fmod(max(0, $left), $dailyHours)),
                    'exceeded' => $left < 0,
                ];
            })
            ->all());
    }
}
