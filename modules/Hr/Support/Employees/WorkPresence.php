<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Employees;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Desk\Cards\ClockCard;
use Modules\Hr\Support\Leave\LeaveRequestPricing;
use Modules\Hr\Support\Leave\LeaveWindow;
use Modules\Hr\Support\Leave\TeamCoverage;

/**
 * The presence dot beside a person: are they in the hub right now, and when
 * they are not, why.
 *
 *   seen in the last five minutes   filled green   "Online"                 for everyone
 *   not seen, on an accepted leave  hollow         "On leave"               / "Offline"
 *   not seen, on a holiday          hollow         "On holiday"             / "Offline"
 *   not seen, outside their hours   hollow         "Outside working hours"  / "Offline"
 *   not seen, nothing explains it   hollow         "Offline"                for everyone
 *   no account, or a former person  nothing
 *
 * "Seen" is users.last_seen_at, stamped by App\Http\Middleware\RecordPresence
 * and read over the same window as the clock card's "online now"
 * (ClockCard::OnlineMinutes). The reason is told only to a viewer who may see
 * this person's absences on the team calendar (TeamCoverage::
 * canSeeAbsencesOf); everyone else reads "Offline". Only an accepted leave
 * counts, and an hourly one only during its own hours. The break between the
 * two periods of a split day is inside working hours; a flexible schedule or
 * one whose hours cannot be read never says "outside working hours".
 *
 * Only the tone and the words leave this class, never the state behind
 * "Offline", so the page carries nothing the viewer may not read. A page of
 * people costs the same handful of queries whatever its length.
 */
final class WorkPresence
{
    public const string Online = 'online';

    public const string Offline = 'offline';

    public function __construct(
        private readonly TeamCoverage $coverage,
        private readonly LeaveRequestPricing $pricing,
    ) {}

    /**
     * One person; null when they get no dot.
     *
     * @return array{tone: string, label: string}|null
     */
    public function forOne(User $viewer, Employee|int|null $employee, ?CarbonInterface $now = null): ?array
    {
        if ($employee === null) {
            return null;
        }

        $id = $employee instanceof Employee ? (int) $employee->getKey() : $employee;

        return $this->for($viewer, [$id], $now)[$id] ?? null;
    }

    /**
     * Many people at once (a list, a table page).
     *
     * @param  iterable<Employee|int|string|null>  $employees
     * @return array<int, array{tone: string, label: string}> keyed by employee id; a person who gets no dot is missing
     */
    public function for(User $viewer, iterable $employees, ?CarbonInterface $now = null): array
    {
        $ids = [];

        foreach ($employees as $employee) {
            $id = (int) ($employee instanceof Employee ? $employee->getKey() : $employee);

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        if ($ids === []) {
            return [];
        }

        $now = CarbonImmutable::instance($now ?? now());
        $seen = $this->lastSeen(array_values($ids));
        $threshold = $now->subMinutes(ClockCard::OnlineMinutes)->toDateTimeString();
        $result = [];
        $offline = [];

        foreach ($seen as $employeeId => $lastSeenAt) {
            if ($lastSeenAt !== null && $lastSeenAt >= $threshold) {
                $result[$employeeId] = ['tone' => self::Online, 'label' => (string) __('hr_presence.online')];
            } else {
                $offline[] = $employeeId;
                $result[$employeeId] = ['tone' => self::Offline, 'label' => (string) __('hr_presence.offline')];
            }
        }

        if ($offline === [] || ! $viewer->can('leaves.view-own')) {
            return $result;
        }

        foreach ($this->reasons($viewer, $offline, $now) as $employeeId => $reason) {
            $result[$employeeId]['label'] = (string) __('hr_presence.'.$reason);
        }

        return $result;
    }

    /**
     * When the account of each active person was last seen, keyed by employee
     * id (null: never). A person without an account is missing.
     *
     * @param  list<int>  $ids
     * @return array<int, string|null>
     */
    private function lastSeen(array $ids): array
    {
        $seen = [];

        $rows = User::query()->toBase()
            ->join('employees', 'employees.id', '=', 'users.employee_id')
            ->whereIn('users.employee_id', $ids)
            ->where('employees.is_active', true)
            ->get(['users.employee_id', 'users.last_seen_at']);

        foreach ($rows as $row) {
            $employeeId = (int) $row->employee_id;
            $lastSeenAt = $row->last_seen_at === null ? null : (string) $row->last_seen_at;

            if (! array_key_exists($employeeId, $seen) || ($lastSeenAt !== null && $lastSeenAt > (string) $seen[$employeeId])) {
                $seen[$employeeId] = $lastSeenAt;
            }
        }

        return $seen;
    }

    /**
     * Why each of these people is away, for those $viewer may be told about:
     * leave | holiday | off_hours. Someone with no reason, or whose absences
     * the viewer may not see, is missing.
     *
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function reasons(User $viewer, array $ids, CarbonImmutable $now): array
    {
        $contracts = $this->runningContracts($ids)
            ->filter(fn (Contract $contract): bool => $this->coverage->canSeeAbsencesOf($viewer, $contract));

        if ($contracts->isEmpty()) {
            return [];
        }

        $visible = array_map('intval', $contracts->keys()->all());
        $onLeave = $this->onLeave($visible, $now);
        $local = $now->setTimezone(ClockCard::Timezone);
        $holidays = $this->holidaysOn($local);
        $reasons = [];

        foreach ($contracts as $employeeId => $contract) {
            $reason = match (true) {
                isset($onLeave[$employeeId]) => 'leave',
                $this->isOnHoliday($contract, $holidays) => 'holiday',
                $this->isOutsideHours($contract, $local) => 'off_hours',
                default => null,
            };

            if ($reason !== null) {
                $reasons[(int) $employeeId] = $reason;
            }
        }

        return $reasons;
    }

    /**
     * The running contract of each person, keyed by employee id: the latest
     * one when several run at once, with what the team calendar rule reads.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Contract>
     */
    private function runningContracts(array $ids): Collection
    {
        return Contract::query()->activeNow()
            ->whereIn('employee_id', $ids)
            ->with([
                'employee:id,city_id,is_active',
                'position.center',
                'position.jobLevel',
                'position.department',
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->unique('employee_id')
            ->keyBy('employee_id');
    }

    /**
     * Who is on an accepted leave right now, as employee id => true. A day
     * leave covers its whole dates; an hourly one its own hours only.
     *
     * @param  list<int>  $ids
     * @return array<int, true>
     */
    private function onLeave(array $ids, CarbonImmutable $now): array
    {
        $today = $now->startOfDay();
        $onLeave = [];

        $leaves = LeaveRequest::query()
            ->where('status', LeaveRequestStatus::Accepted->value)
            ->where('start_at', '<', $today->addDay()->toDateTimeString())
            ->where('end_at', '>=', $today->toDateTimeString())
            ->whereHas('leaveBalance.contract', fn (Builder $contract) => $contract->whereIn('employee_id', $ids))
            ->with(['leaveBalance:id,contract_id', 'leaveBalance.contract:id,employee_id'])
            ->get(['id', 'leave_balance_id', 'type', 'start_at', 'end_at']);

        foreach ($leaves as $leave) {
            $employeeId = (int) $leave->leaveBalance?->contract?->employee_id;

            if ($employeeId > 0 && ($leave->type !== LeaveWindow::HOUR || ($leave->start_at->lte($now) && $leave->end_at->gt($now)))) {
                $onLeave[$employeeId] = true;
            }
        }

        return $onLeave;
    }

    /**
     * The national and project holidays that fall on this day, with the
     * program × governorate pairs of the project ones. Birthday rows are
     * windows, not days, and stay out.
     *
     * @return Collection<int, Holiday>
     */
    private function holidaysOn(CarbonImmutable $local): Collection
    {
        $day = CarbonImmutable::parse($local->toDateString());

        return Holiday::query()
            ->where('kind', '!=', Holiday::KIND_BIRTHDAY)
            ->occurringBetween($day, $day)
            ->with('projects')
            ->get()
            ->filter(fn (Holiday $holiday): bool => $this->pricing->spread([$holiday], $day, $day) !== [])
            ->values();
    }

    /**
     * Does one of the day's holidays reach this contract: a national one, or
     * a project one for its program in its governorate (the center's city,
     * else the position's).
     *
     * @param  Collection<int, Holiday>  $holidays
     */
    private function isOnHoliday(Contract $contract, Collection $holidays): bool
    {
        $position = $contract->position;
        $programId = (int) $position?->program_id;
        $cityId = (int) ($position?->center?->city_id ?? $position?->city_id);

        return $holidays->contains(fn (Holiday $holiday): bool => $holiday->isNational()
            || ($programId > 0 && $cityId > 0 && $holiday->projects->contains(
                fn ($project): bool => (int) $project->program_id === $programId && (int) $project->city_id === $cityId
            )));
    }

    /**
     * Is this moment outside the contract's fixed hours: an off day, or
     * before the first period or after the last. False when the schedule is
     * flexible or cannot be read, because "outside hours" would be a guess.
     */
    private function isOutsideHours(Contract $contract, CarbonImmutable $local): bool
    {
        if ((int) $contract->schedule_mode !== ScheduleMode::Fixed->value) {
            return false;
        }

        $from = $contract->work_day_from;
        $to = $contract->work_day_to;
        $first = $this->period($contract->work_hours_from, $contract->work_hours_to);

        if ($from === null || $to === null || $from < 0 || $from > 6 || $to < 0 || $to > 6 || $first === null) {
            return false;
        }

        $second = $this->period($contract->work_hours_2_from, $contract->work_hours_2_to);
        $end = $second !== null && $second[0] >= $first[1] ? $second[1] : $first[1];
        $dow = $local->dayOfWeek;
        $working = $from <= $to ? $dow >= $from && $dow <= $to : $dow >= $from || $dow <= $to;
        $minute = $local->hour * 60 + $local->minute;

        return ! $working || $minute < $first[0] || $minute >= $end;
    }

    /**
     * A period of the day in minutes since midnight, or null when unreadable.
     *
     * @return array{0: int, 1: int}|null
     */
    private function period(?string $from, ?string $to): ?array
    {
        $minute = function (?string $time): ?int {
            if (! is_string($time) || ! preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $m) || (int) $m[1] > 23 || (int) $m[2] > 59) {
                return null;
            }

            return (int) $m[1] * 60 + (int) $m[2];
        };

        $start = $minute($from);
        $end = $minute($to);

        return $start !== null && $end !== null && $end > $start ? [$start, $end] : null;
    }
}
