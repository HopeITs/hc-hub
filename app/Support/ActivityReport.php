<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Activity;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\City;
use Modules\Core\Models\User;

/**
 * One place that turns the activity log into the figures of the Activity
 * report (who uses the hub most, when and where), so the page and its export
 * can never disagree.
 */
final class ActivityReport
{
    public const PERIODS = [7, 30, 90, 365];

    public function __construct(public readonly int $days = 30) {}

    public static function normaliseDays(int $days): int
    {
        return in_array($days, self::PERIODS, true) ? $days : 30;
    }

    /**
     * @return array{
     *     days: int,
     *     total: int,
     *     activeUsers: int,
     *     topUsers: Collection<int, array{name: string, actions: int, last: ?string}>,
     *     daily: Collection<int, array{date: string, actions: int}>,
     *     hours: Collection<int, array{hour: int, actions: int}>,
     *     cities: Collection<int, array{name: string, actions: int, users: int}>,
     *     inactive: Collection<int, array{name: string, last_seen: ?CarbonImmutable}>,
     *     inactiveLimit: int
     * }
     */
    public function build(int $inactiveLimit = 15): array
    {
        return [
            'days' => self::normaliseDays($this->days),
            'total' => $this->base()->count(),
            'activeUsers' => $this->base()->whereNotNull('actor_id')->distinct()->count('actor_id'),
            'topUsers' => $this->topUsers(),
            'daily' => $this->daily(),
            'hours' => $this->hours(),
            'cities' => $this->cities(),
            'inactive' => $this->inactive(),
            'inactiveLimit' => $inactiveLimit,
        ];
    }

    private function from(): CarbonImmutable
    {
        return CarbonImmutable::now()->subDays(self::normaliseDays($this->days) - 1)->startOfDay();
    }

    /**
     * @return Builder<Activity>
     */
    private function base(): Builder
    {
        return Activity::query()->where('activities.created_at', '>=', $this->from());
    }

    /**
     * @return Collection<int, array{name: string, actions: int, last: ?string}>
     */
    private function topUsers(): Collection
    {
        $rows = $this->base()
            ->whereNotNull('actor_id')
            ->select('actor_id', DB::raw('count(*) as actions'), DB::raw('max(created_at) as last_action'))
            ->groupBy('actor_id')
            ->orderByDesc('actions')
            ->limit(5)
            ->get();

        $users = User::query()->select(['id', 'email', 'employee_id'])->whereIn('id', $rows->pluck('actor_id'))->get()->keyBy('id');

        return $rows->map(fn (Activity $row): array => [
            'name' => (string) ($users->get($row->actor_id)?->name ?? '-'),
            'actions' => (int) $row->getAttribute('actions'),
            'last' => $row->getAttribute('last_action') === null ? null : (string) $row->getAttribute('last_action'),
        ])->values();
    }

    /**
     * Every day of the period, zero-filled, oldest first.
     *
     * @return Collection<int, array{date: string, actions: int}>
     */
    private function daily(): Collection
    {
        $counts = $this->base()
            ->select(DB::raw('date(created_at) as d'), DB::raw('count(*) as actions'))
            ->groupBy('d')
            ->pluck('actions', 'd');

        $days = collect();

        for ($day = $this->from(); $day->lte(CarbonImmutable::now()); $day = $day->addDay()) {
            $key = $day->toDateString();
            $days->push(['date' => $key, 'actions' => (int) ($counts[$key] ?? 0)]);
        }

        return $days;
    }

    /**
     * Actions per hour of the day, 0-23, zero-filled.
     *
     * @return Collection<int, array{hour: int, actions: int}>
     */
    private function hours(): Collection
    {
        $counts = $this->base()
            ->select(DB::raw('hour(created_at) as h'), DB::raw('count(*) as actions'))
            ->groupBy('h')
            ->pluck('actions', 'h');

        return collect(range(0, 23))->map(fn (int $hour): array => ['hour' => $hour, 'actions' => (int) ($counts[$hour] ?? 0)]);
    }

    /**
     * Actions and distinct people per city of the acting employee.
     *
     * @return Collection<int, array{name: string, actions: int, users: int}>
     */
    private function cities(): Collection
    {
        $rows = $this->base()
            ->join('users', 'users.id', '=', 'activities.actor_id')
            ->join('employees', 'employees.id', '=', 'users.employee_id')
            ->select('employees.city_id', DB::raw('count(*) as actions'), DB::raw('count(distinct users.id) as people'))
            ->groupBy('employees.city_id')
            ->orderByDesc('actions')
            ->get();

        $names = City::query()->whereIn('id', $rows->pluck('city_id')->filter())->pluck(City::nameColumn(), 'id');

        return $rows->map(fn (Activity $row): array => [
            'name' => (string) ($names->get($row->getAttribute('city_id')) ?? '-'),
            'actions' => (int) $row->getAttribute('actions'),
            'users' => (int) $row->getAttribute('people'),
        ])->values();
    }

    /**
     * People with an active employee record who did nothing in the period.
     *
     * @return Collection<int, array{name: string, last_seen: ?CarbonImmutable}>
     */
    private function inactive(): Collection
    {
        $actedIds = $this->base()->whereNotNull('actor_id')->select('actor_id');

        return User::query()
            ->select(['id', 'email', 'employee_id', 'last_seen_at'])
            ->whereIn('employee_id', DB::table('employees')->where('is_active', true)->select('id'))
            ->whereNotIn('id', $actedIds)
            ->orderBy('last_seen_at')
            ->get()
            ->map(fn (User $user): array => [
                'name' => $user->name,
                'last_seen' => $user->last_seen_at === null ? null : CarbonImmutable::instance($user->last_seen_at),
            ])
            ->values();
    }
}
