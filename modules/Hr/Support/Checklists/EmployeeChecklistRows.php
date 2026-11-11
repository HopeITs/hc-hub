<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Checklists;

use Modules\Hr\Enums\ChecklistItemOrder;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\ChecklistItem;
use Modules\Hr\Models\DefaultDeliverable;
use Modules\Hr\Models\Employee;

/**
 * The editable checklist of one employee: one row per checklist item that
 * applies to them, pre-filled from what is already assigned (the old
 * ChecklistForm::setEmployeeAndAllItems).
 *
 * A record created within the last month gets the probation-contract items;
 * an older one gets every other item. An employee with nothing assigned yet
 * starts with every row ticked and the governorate's default deliverer.
 */
final class EmployeeChecklistRows
{
    /**
     * @return list<array{checklist_id: int|null, item_id: int, item_name: string, is_selected: bool, delivered_id: int|null, receiver_id: int|null, delivered_status: string|null, returned_status: string|null, is_returned: bool}>
     */
    public static function for(Employee $employee): array
    {
        $probation = ChecklistItemOrder::ProbationContract->value;
        $isRecent = $employee->created_at !== null && $employee->created_at->greaterThan(now()->subMonth());

        $items = ChecklistItem::query()
            ->when($isRecent, fn ($query) => $query->where('order', $probation), fn ($query) => $query->where('order', '!=', $probation))
            ->orderBy('order')
            ->orderBy('id')
            ->get(['id', 'name']);

        // The newest row per item, as the save reads it.
        $saved = Checklist::query()
            ->where('employee_id', $employee->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'item_id', 'delivered_id', 'receiver_id', 'delivered_status', 'returned_status'])
            ->keyBy('item_id');

        $isNew = $saved->isEmpty();

        $defaults = DefaultDeliverable::query()
            ->where('city_id', $employee->city_id)
            ->pluck('employee_id', 'item_id');

        $rows = [];

        foreach ($items as $item) {
            $row = $saved->get($item->id);
            $fullyReturned = $row?->returned_status === ChecklistWorkflow::RECEIVED;
            $deliveredId = $row?->delivered_id;

            if ($fullyReturned || $deliveredId === null) {
                $default = $defaults->get($item->id);
                $deliveredId = $default === null ? null : (int) $default;
            }

            $rows[] = [
                'checklist_id' => $row?->id,
                'item_id' => $item->id,
                'item_name' => $item->name,
                'is_selected' => $isNew || $row !== null,
                'delivered_id' => $deliveredId,
                'receiver_id' => $fullyReturned ? null : $row?->receiver_id,
                'delivered_status' => $fullyReturned ? null : $row?->delivered_status,
                'returned_status' => $fullyReturned ? null : $row?->returned_status,
                // Received back: HR may close the cycle (the row is removed) or tick it again to start a new one.
                'is_returned' => $fullyReturned,
            ];
        }

        return $rows;
    }
}
