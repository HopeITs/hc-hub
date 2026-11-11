<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;

/**
 * Birthdays: who in the viewer's team has a birthday in the coming seven
 * days. Only the day and month are shown, never the year. Someone born on
 * 29 February is greeted on 1 March in a year without one.
 */
final class BirthdaysCard extends HrCard
{
    public const int Days = 7;

    public function key(): string
    {
        return 'birthdays';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null;
    }

    public function icon(): string
    {
        return 'users';
    }

    public function size(): string
    {
        return 'S';
    }

    public function layout(): string
    {
        return 'agenda';
    }

    public function figures(User $user): array
    {
        $people = $this->within($user, self::Days);
        $today = CarbonImmutable::today()->toDateString();

        return [
            $this->figure(__('hr_desk.today'), $people->where('date', $today)->count(), 'good'),
            $this->figure(__('hr_desk.cards.birthdays.this_week'), $people->count(), 'active'),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->within($user, self::Days)->map(function (array $person): array {
            $date = CarbonImmutable::parse($person['date']);
            $isToday = $date->isToday();

            return $this->shaped(
                $this->row('bd-'.$person['id'], $person['name'], $date->translatedFormat('l'), ['label' => $this->away($date), 'tone' => $isToday ? 'good' : 'neutral'], $this->link('hr.team.index'), $person['words']),
                lead: $this->day($date, $isToday ? 'good' : 'neutral'),
            );
        });
    }

    /**
     * The viewer's team whose birthday falls in the next $days days (today
     * included), soonest first. Also read by the HR calendar (CalendarCard).
     *
     * @return Collection<int, array{id: int, name: string, words: string, date: string}>
     */
    public function within(User $user, int $days): Collection
    {
        $ids = $this->viewer($user)->teamIds();

        if ($ids === []) {
            return new Collection;
        }

        $today = CarbonImmutable::today();
        $week = [];

        for ($day = $today; $day->lte($today->addDays($days - 1)); $day = $day->addDay()) {
            $week[$day->format('m-d')] = $day->toDateString();
        }

        if (! isset($week['02-29']) && isset($week['03-01'])) {
            $week['02-29'] = $week['03-01'];
        }

        /* Birthdays do not change during a day: one read per team and day. */
        $rows = Cache::remember('hr.desk.birthdays.'.md5(implode(',', $ids)).'.'.$days.'.'.app()->getLocale().'.'.$today->toDateString(), 900, fn (): array => Employee::query()
            ->select([...Employee::NameColumns, 'actual_dob'])
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->whereNotNull('actual_dob')
            ->whereRaw("DATE_FORMAT(actual_dob, '%m-%d') IN (".implode(', ', array_fill(0, count($week), '?')).')', array_keys($week))
            ->get()
            ->map(fn (Employee $employee): array => [
                'id' => (int) $employee->id,
                'name' => (string) $employee->name,
                'words' => (string) $employee->secondary_name,
                'date' => $week[$employee->actual_dob->format('m-d')] ?? '',
            ])
            ->filter(fn (array $person): bool => $person['date'] !== '')
            ->sortBy([['date', 'asc'], ['name', 'asc']])
            ->values()
            ->all());

        return collect($rows);
    }
}
