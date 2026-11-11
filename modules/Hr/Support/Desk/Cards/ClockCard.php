<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ScheduleMode;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Holiday;

/**
 * My working day: today's hours from the viewer's contract (fixed schedules
 * only), how much of the day is done, and which colleagues are online now.
 *
 * A day is off outside the contract's weekday range or on a holiday that
 * reaches the viewer's program and governorate. "Online" is an account seen
 * in the last five minutes (users.last_seen_at).
 */
final class ClockCard extends HrCard
{
    public const string Timezone = 'Asia/Damascus';

    public const int OnlineMinutes = 5;

    public function key(): string
    {
        return 'clock';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null;
    }

    public function icon(): string
    {
        return 'dot';
    }

    public function size(): string
    {
        return 'S';
    }

    public function layout(): string
    {
        return 'people';
    }

    public function figures(User $user): array
    {
        $now = CarbonImmutable::now(self::Timezone);
        $day = $this->today($user, $now);
        $figures = [[...$this->figure($now->translatedFormat('l j F'), $now->format('H:i'), 'active'), 'clock' => true]];

        if ($day === null) {
            return $figures;
        }

        if ($day['off']) {
            return [...$figures, $this->figure((string) ($day['holiday'] ?? __('hr_desk.cards.clock.day_off')), (string) __('hr_desk.cards.clock.off'), 'good')];
        }

        $minute = $now->hour * 60 + $now->minute;
        $total = 0;
        $done = 0;

        foreach ($day['periods'] as [$start, $end]) {
            $total += $end - $start;
            $done += max(0, min($minute, $end) - $start);
        }

        $clock = fn (int $minutes): string => sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
        $hours = implode(' · ', array_map(fn (array $period): string => sprintf('%02d:%02d', intdiv($period[0], 60), $period[0] % 60).'–'.sprintf('%02d:%02d', intdiv($period[1], 60), $period[1] % 60), $day['periods']));

        return [
            ...$figures,
            $this->figure((string) __('hr_desk.cards.clock.hours'), $hours),
            $this->figure((string) __('hr_desk.cards.clock.done'), $clock($done).' / '.$clock($total), $done >= $total ? 'good' : 'warn'),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        $ids = $this->viewer($user)->othersIds();

        if ($ids === []) {
            return new Collection;
        }

        $online = User::query()->toBase()
            ->whereIn('employee_id', $ids)
            ->where('last_seen_at', '>=', now()->subMinutes(self::OnlineMinutes))
            ->distinct()
            ->limit(self::RowLimit)
            ->pluck('employee_id')
            ->all();

        if ($online === []) {
            return new Collection;
        }

        return Employee::query()->select(Employee::NameColumns)->whereIn('id', $online)->where('is_active', true)->get()
            ->sortBy('name')->values()
            ->map(fn (Employee $employee): array => $this->shaped(
                $this->row('on-'.$employee->id, (string) $employee->name, '', ['label' => (string) __('hr_desk.cards.clock.online'), 'tone' => 'good'], '', (string) $employee->secondary_name),
                lead: $this->face((int) $employee->id, (string) $employee->name),
            ));
    }

    /**
     * Today on the viewer's contract: null when the schedule is not a fixed one.
     *
     * @return array{off: bool, periods: list<array{0: int, 1: int}>, holiday: string|null}|null
     */
    private function today(User $user, CarbonImmutable $now): ?array
    {
        $viewer = $this->viewer($user);
        $contract = $viewer->contract();

        if ($contract === null || $this->scheduleMode($contract) !== ScheduleMode::Fixed) {
            return null;
        }

        $from = $contract->work_day_from;
        $to = $contract->work_day_to;
        $first = $this->period($contract->work_hours_from, $contract->work_hours_to);

        if ($from === null || $to === null || $from < 0 || $from > 6 || $to < 0 || $to > 6 || $first === null) {
            return null;
        }

        $periods = [$first];
        $second = $this->period($contract->work_hours_2_from, $contract->work_hours_2_to);

        if ($second !== null && $second[0] >= $first[1]) {
            $periods[] = $second;
        }

        $dow = $now->dayOfWeek;
        $working = $from <= $to ? $dow >= $from && $dow <= $to : $dow >= $from || $dow <= $to;
        $day = CarbonImmutable::parse($now->toDateString());
        $holiday = Holiday::query()->select(['id', 'name_en', 'name_ar', 'start_date', 'end_date', 'is_recurring'])
            ->applicableTo($viewer->programId(), $viewer->cityId())
            ->where('is_recurring', false)
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->first();

        return ['off' => ! $working || $holiday !== null, 'periods' => $periods, 'holiday' => $holiday === null ? null : (string) $holiday->name];
    }

    private function scheduleMode(Contract $contract): mixed
    {
        $mode = $contract->schedule_mode;

        return $mode instanceof ScheduleMode ? $mode : ScheduleMode::tryFrom((int) $mode);
    }

    /**
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
