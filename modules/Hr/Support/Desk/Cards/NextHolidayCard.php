<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Support\Desk\UpcomingHolidays;

/**
 * Next holiday: the holidays that reach the viewer's program and governorate
 * in the coming sixty days, the nearest first.
 */
final class NextHolidayCard extends HrCard
{
    /**
     * How many days ahead the card looks.
     */
    public const int Window = 60;

    public function key(): string
    {
        return 'next-holiday';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null;
    }

    public function register(): ?string
    {
        return 'hr.leave.holidays.index';
    }

    public function icon(): string
    {
        return 'cal';
    }

    public function size(): string
    {
        return 'S';
    }

    public function height(): int
    {
        return 3;
    }

    public function layout(): string
    {
        return 'agenda';
    }

    public function figures(User $user): array
    {
        $next = $this->upcoming($user)->first();

        return $next === null ? [] : [
            $this->figure((string) $next['name'], $this->away($next['from']), 'active'),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->upcoming($user)->map(fn (array $holiday): array => $this->shaped(
            $this->row('nh-'.$holiday['id'].'-'.$holiday['from']->toDateString(), $holiday['name'], $this->span($holiday['from'], $holiday['to']).' · '.trans_choice('hr_desk.day_count', $holiday['length'], ['count' => $holiday['length']]), ['label' => $this->away($holiday['from']), 'tone' => 'active'], $this->link('hr.leave.holidays.index')),
            lead: $this->day($holiday['from'], 'active'),
        ));
    }

    /**
     * @return Collection<int, array{id: int, name: string, from: CarbonImmutable, to: CarbonImmutable, length: int}>
     */
    private function upcoming(User $user): Collection
    {
        $viewer = $this->viewer($user);

        return app(UpcomingHolidays::class)->for($viewer->programId(), $viewer->cityId(), self::Window);
    }
}
