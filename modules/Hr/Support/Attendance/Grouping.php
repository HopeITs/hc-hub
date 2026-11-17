<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Attendance;

use Illuminate\Support\Collection;

/**
 * Timesheets laid out the way HR reads them: for each city, the people grouped by the project
 * they work on (program) or by department. Used by the timesheet page and the printout, so both
 * order and count the same way.
 */
class Grouping
{
    public const PROJECT = 'program';

    public const DEPARTMENT = 'department';

    public static function normalize(?string $by): string
    {
        return $by === self::DEPARTMENT ? self::DEPARTMENT : self::PROJECT;
    }

    /**
     * The group id a person belongs to (null = none set), as printed in the "group" parameter.
     *
     * @param  array{sheet: array<string, mixed>}  $item
     */
    public static function groupId(array $item, string $by): ?int
    {
        return $item['sheet']['placement'][$by.'_id'] ?? null;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items  each with 'employee' and 'sheet'
     * @return Collection<int, array{id: int, name: ?string, groups: Collection<int, array<string, mixed>>, totals: array<string, int|float>}>
     */
    public function group(Collection $items, string $by): Collection
    {
        $by = self::normalize($by);

        return $items
            ->groupBy(fn (array $item): int => (int) $item['employee']->city_id)
            ->map(function (Collection $inCity, int $cityId) use ($by): array {
                $groups = $inCity
                    ->groupBy(fn (array $item): int => self::groupId($item, $by) ?? 0)
                    ->map(function (Collection $members, int $groupId) use ($by): array {
                        $members = $members->sortBy(fn (array $item): string => mb_strtolower((string) $item['employee']->name))->values();

                        return [
                            'key' => $by.'-'.($groupId ?: 'none'),
                            'id' => $groupId ?: null,
                            'name' => $members->first()['sheet']['placement'][$by] ?? null,
                            'items' => $members,
                            'totals' => $this->totals($members),
                        ];
                    })
                    // Named groups by name; the people with none set come last.
                    ->sortBy(fn (array $group): array => [$group['id'] === null ? 1 : 0, mb_strtolower((string) $group['name'])])
                    ->values();

                return [
                    'id' => $cityId,
                    'name' => $inCity->first()['employee']->city?->name,
                    'groups' => $groups,
                    'totals' => $this->totals($inCity),
                ];
            })
            ->sortBy(fn (array $block): string => mb_strtolower((string) $block['name']))
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array{people: int, to_decide: int, overtime_hours: float, overtime_days: float}
     */
    public function totals(Collection $items): array
    {
        return [
            'people' => $items->count(),
            'to_decide' => (int) $items->sum(fn (array $item): int => $item['sheet']['totals']['to_decide']),
            'overtime_hours' => round((float) $items->sum(fn (array $item): float => $item['sheet']['totals']['overtime_hours']), 2),
            'overtime_days' => round((float) $items->sum(fn (array $item): float => $item['sheet']['totals']['overtime_days']), 2),
        ];
    }
}
