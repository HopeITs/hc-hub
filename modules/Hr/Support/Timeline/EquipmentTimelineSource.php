<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Timeline;

use App\Support\Modules\Contracts\TimelineSource;
use Carbon\CarbonImmutable;
use Modules\Core\Models\User;
use Modules\Hr\Models\Checklist;

/**
 * Equipment on an employee's timeline: each item handed to them and each one
 * they gave back, read from checklists (the equipment hand-over records).
 */
final class EquipmentTimelineSource implements TimelineSource
{
    public function key(): string
    {
        return 'equipment';
    }

    public function label(): string
    {
        return __('timeline.sources.equipment');
    }

    public function icon(): string
    {
        return 'computer-desktop';
    }

    public function allows(User $viewer, int $employeeId): bool
    {
        return ContractsTimelineSource::readsRecords($viewer, $employeeId);
    }

    public function entries(User $viewer, int $employeeId): array
    {
        $entries = [];

        foreach (Checklist::query()->where('employee_id', $employeeId)->with('item:id,name')->get() as $handover) {
            $item = trim(implode(' ', array_filter([(string) $handover->item?->name, (string) $handover->brand, (string) $handover->model])));
            $item = $item === '' ? null : $item;

            if ($handover->delivered_at !== null) {
                $entries[] = ['date' => CarbonImmutable::parse($handover->delivered_at), 'title' => __('timeline.equipment.received'), 'detail' => $item, 'href' => null];
            }

            if ($handover->returned_at !== null) {
                $entries[] = ['date' => CarbonImmutable::parse($handover->returned_at), 'title' => __('timeline.equipment.returned'), 'detail' => $item, 'href' => null];
            }
        }

        return $entries;
    }
}
