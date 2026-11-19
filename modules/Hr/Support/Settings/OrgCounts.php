<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Settings;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Position;

/**
 * Headcounts for the read-only programs and centers pages: positions and
 * the people currently working (an active contract dated today), grouped by
 * program, center or governorate in one query each. A regional HR role's
 * governorates narrow every figure.
 */
final class OrgCounts
{
    /**
     * Positions per group: count, seats, distinct governorates and centers.
     *
     * @param  'program_id'|'center_id'|'city_id'  $groupBy
     * @param  list<int>|null  $cityIds
     * @param  array<string, int>  $where  position column => value
     * @return Collection<int, object{positions: int, seats: int, cities: int, centers: int}>
     */
    public static function positions(string $groupBy, ?array $cityIds = null, array $where = []): Collection
    {
        return Position::query()
            ->toBase()
            ->when($cityIds !== null, fn ($query) => $query->whereIn('city_id', $cityIds))
            ->where($where)
            ->whereNotNull($groupBy)
            ->groupBy($groupBy)
            ->selectRaw("{$groupBy} as group_id, count(*) as positions, coalesce(sum(max_emp), 0) as seats, count(distinct city_id) as cities, count(distinct center_id) as centers")
            ->get()
            ->keyBy('group_id')
            ->map(fn (object $row): object => (object) [
                'positions' => (int) $row->positions,
                'seats' => (int) $row->seats,
                'cities' => (int) $row->cities,
                'centers' => (int) $row->centers,
            ]);
    }

    /**
     * People currently working per group (distinct employees).
     *
     * @param  'program_id'|'center_id'|'city_id'  $groupBy
     * @param  list<int>|null  $cityIds
     * @param  array<string, int>  $where  position column => value
     * @return Collection<int, int>
     */
    public static function employees(string $groupBy, ?array $cityIds = null, array $where = []): Collection
    {
        $query = self::activeAssignments($cityIds, $where)
            ->whereNotNull('positions.'.$groupBy)
            ->groupBy('positions.'.$groupBy)
            ->selectRaw("positions.{$groupBy} as group_id, count(distinct contracts.employee_id) as employees");

        return $query->toBase()->get()->mapWithKeys(fn (object $row): array => [(int) $row->group_id => (int) $row->employees]);
    }

    /**
     * Distinct people currently working on the matching positions.
     *
     * @param  list<int>|null  $cityIds
     * @param  array<string, int>  $where
     */
    public static function headcount(?array $cityIds = null, array $where = []): int
    {
        return (int) self::activeAssignments($cityIds, $where)->toBase()->distinct()->count('contracts.employee_id');
    }

    /**
     * One program at the centers linked to it (center_program): per center,
     * its positions placed there plus those without a center in the
     * center's governorate (Study Zone's, shared by its centers there), and
     * the distinct people on them now.
     *
     * @param  list<int>|null  $cityIds
     * @return Collection<int, object{positions: int, seats: int, employees: int}>
     */
    public static function linkedCenters(int $programId, ?array $cityIds = null): Collection
    {
        $centerIds = DB::table('center_program')
            ->join('centers', 'centers.id', '=', 'center_program.center_id')
            ->where('center_program.program_id', $programId)
            ->when($cityIds !== null, fn ($query) => $query->whereIn('centers.city_id', $cityIds))
            ->pluck('centers.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($centerIds === []) {
            return collect();
        }

        $serving = fn (JoinClause $join) => $join
            ->on('serving.id', '=', 'positions.center_id')
            ->orWhere(fn (JoinClause $shared) => $shared
                ->whereNull('positions.center_id')
                ->whereColumn('serving.city_id', 'positions.city_id'));

        $employees = self::activeAssignments($cityIds, ['program_id' => $programId])
            ->toBase()
            ->join('centers as serving', $serving)
            ->whereIn('serving.id', $centerIds)
            ->groupBy('serving.id')
            ->selectRaw('serving.id as center_id, count(distinct contracts.employee_id) as employees')
            ->pluck('employees', 'center_id');

        $positions = Position::query()
            ->toBase()
            ->join('centers as serving', $serving)
            ->whereIn('serving.id', $centerIds)
            ->where('positions.program_id', $programId)
            ->when($cityIds !== null, fn ($query) => $query->whereIn('positions.city_id', $cityIds))
            ->groupBy('serving.id')
            ->selectRaw('serving.id as center_id, count(*) as positions, coalesce(sum(positions.max_emp), 0) as seats')
            ->get()
            ->keyBy('center_id');

        return collect($centerIds)->mapWithKeys(fn (int $centerId): array => [$centerId => (object) [
            'positions' => (int) ($positions->get($centerId)->positions ?? 0),
            'seats' => (int) ($positions->get($centerId)->seats ?? 0),
            'employees' => (int) ($employees->get($centerId) ?? 0),
        ]]);
    }

    /**
     * Active contracts dated today, joined to their position.
     *
     * @param  list<int>|null  $cityIds
     * @param  array<string, int>  $where  position column => value
     * @return Builder<Contract>
     */
    public static function activeAssignments(?array $cityIds = null, array $where = []): Builder
    {
        $query = Contract::query()
            ->activeNow()
            ->join('positions', 'positions.id', '=', 'contracts.position_id')
            ->when($cityIds !== null, fn (Builder $contracts) => $contracts->whereIn('positions.city_id', $cityIds));

        foreach ($where as $column => $value) {
            $query->where('positions.'.$column, $value);
        }

        return $query;
    }

    /**
     * "Friday, Saturday" from a center's stored weekend day numbers
     * (0 = Sunday ... 6 = Saturday), in the interface language.
     */
    public static function weekendDays(?string $stored): string
    {
        $sunday = CarbonImmutable::parse('2026-01-04');

        return collect(explode(',', (string) $stored))
            ->map(fn (string $day): string => trim($day))
            ->filter(fn (string $day): bool => $day !== '' && ctype_digit($day) && (int) $day <= 6)
            ->map(fn (string $day): int => (int) $day)
            ->unique()
            ->sort()
            ->map(fn (int $day): string => $sunday->addDays($day)->translatedFormat('l'))
            ->implode(app()->getLocale() === 'ar' ? '، ' : ', ');
    }

    /**
     * "09:00 – 17:00" from stored open and close times.
     */
    public static function hours(?string $openAt, ?string $closeAt): string
    {
        if (blank($openAt) || blank($closeAt)) {
            return '';
        }

        return substr((string) $openAt, 0, 5).' – '.substr((string) $closeAt, 0, 5);
    }
}
