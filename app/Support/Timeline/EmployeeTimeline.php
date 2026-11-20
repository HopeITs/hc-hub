<?php

declare(strict_types=1);

namespace App\Support\Timeline;

use App\Support\Modules\Contracts\TimelineSource;
use App\Support\Modules\ModuleRegistry;
use Carbon\CarbonImmutable;
use Modules\Core\Models\User;

/**
 * One employee's history, gathered from every module's TimelineSource the
 * viewer may read (roadmap 4.7, D6): newest first, undated facts last.
 */
final class EmployeeTimeline
{
    public function __construct(private readonly ModuleRegistry $registry) {}

    /**
     * The sources this viewer may read about this employee.
     *
     * @return list<TimelineSource>
     */
    public function sourcesFor(User $viewer, int $employeeId): array
    {
        return array_values(array_filter(
            $this->registry->timelineSources(),
            fn (TimelineSource $source): bool => $source->allows($viewer, $employeeId),
        ));
    }

    /**
     * @return list<array{source: string, label: string, icon: string, date: CarbonImmutable|null, title: string, detail: string|null, href: string|null}>
     */
    public function entries(User $viewer, int $employeeId): array
    {
        $entries = [];

        foreach ($this->sourcesFor($viewer, $employeeId) as $source) {
            foreach ($source->entries($viewer, $employeeId) as $entry) {
                $entries[] = ['source' => $source->key(), 'label' => $source->label(), 'icon' => $source->icon(), ...$entry];
            }
        }

        usort($entries, function (array $first, array $second): int {
            if ($first['date'] === null || $second['date'] === null) {
                return ($first['date'] === null) <=> ($second['date'] === null);
            }

            return $second['date'] <=> $first['date'];
        });

        return $entries;
    }
}
