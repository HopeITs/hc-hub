<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Hr\Models\Holiday;

/**
 * The holidays that reach one program and governorate over the coming days,
 * the nearest first. A recurring holiday is read at this year's date, or
 * next year's once this year's has passed.
 */
final class UpcomingHolidays
{
    /**
     * @return Collection<int, array{id: int, name: string, from: CarbonImmutable, to: CarbonImmutable, length: int}>
     */
    public function for(?int $programId, ?int $cityId, int $days): Collection
    {
        $today = CarbonImmutable::today();
        $until = $today->addDays($days);

        /* Holidays change a few times a year: one read per program, governorate and day, kept ten minutes. */
        $rows = Cache::remember("hr.desk.holidays.{$programId}.{$cityId}.{$days}.".app()->getLocale().'.'.$today->toDateString(), 600, fn (): array => Holiday::query()
            ->select(['id', 'name_en', 'name_ar', 'start_date', 'end_date', 'is_recurring', 'scope'])
            ->applicableTo($programId, $cityId)
            ->occurringBetween($today, $until)
            ->get()
            ->map(fn (Holiday $holiday): array => [
                'id' => (int) $holiday->id,
                'name' => (string) $holiday->name,
                'start' => $holiday->start_date->toDateString(),
                'end' => ($holiday->end_date ?? $holiday->start_date)->toDateString(),
                'recurring' => (bool) $holiday->is_recurring,
            ])->all());

        return collect($rows)->map(function (array $row) use ($today, $until): ?array {
            $from = CarbonImmutable::parse($row['start']);
            $to = CarbonImmutable::parse($row['end']);

            if ($row['recurring']) {
                $length = (int) $from->diffInDays($to);
                $from = $from->year($today->year);
                $from = $from->addDays($length)->lt($today) ? $from->addYear() : $from;
                $to = $from->addDays($length);
            }

            if ($to->lt($today) || $from->gt($until)) {
                return null;
            }

            return ['id' => $row['id'], 'name' => $row['name'], 'from' => $from, 'to' => $to, 'length' => (int) $from->diffInDays($to) + 1];
        })->filter()->sortBy(fn (array $holiday): int => $holiday['from']->getTimestamp())->values();
    }
}
