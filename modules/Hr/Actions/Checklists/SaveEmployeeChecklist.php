<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Checklists;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Checklists\ChecklistNotifier;
use Modules\Hr\Support\Checklists\ChecklistWorkflow;

/**
 * Save the checklist HR ticked for one employee (the old ChecklistForm::save).
 *
 * Per item: an unticked item that was never delivered is removed; a ticked
 * one is created as "pending delivery" (also after a completed return, which
 * starts a new cycle), or has its deliverer / receiver updated. Naming a
 * receiver on a delivered item starts its return; clearing it cancels that.
 * The new deliverer or receiver is notified, unless it is the person saving.
 */
final class SaveEmployeeChecklist
{
    public function __construct(private readonly ChecklistNotifier $notifier) {}

    /**
     * @param  list<array<string, mixed>>  $rows  item_id, is_selected, delivered_id, receiver_id
     * @return int how many people were notified
     *
     * @throws ValidationException
     */
    public function handle(Employee $employee, array $rows): int
    {
        Gate::authorize('manage', [Checklist::class, $employee]);

        $actorEmployeeId = Auth::user()?->employee_id;

        $rows = Validator::make(['rows' => array_values($rows)], [
            'rows' => ['array'],
            'rows.*.item_id' => ['required', 'integer', 'exists:checklist_items,id'],
            'rows.*.is_selected' => ['boolean'],
            'rows.*.delivered_id' => ['nullable', 'integer', 'exists:employees,id'],
            'rows.*.receiver_id' => ['nullable', 'integer', 'exists:employees,id'],
        ])->validate()['rows'] ?? [];

        return DB::transaction(function () use ($employee, $rows, $actorEmployeeId): int {
            $notified = 0;

            foreach ($rows as $index => $row) {
                $itemId = (int) $row['item_id'];
                $selected = (bool) ($row['is_selected'] ?? false);

                $existing = Checklist::query()
                    ->where('employee_id', $employee->id)
                    ->where('item_id', $itemId)
                    ->latest()
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                $originalDeliveredId = $existing?->delivered_id;
                $originalReceiverId = $existing?->receiver_id;

                if (! $selected) {
                    if ($existing === null || $existing->delivered_status !== ChecklistWorkflow::DELIVERED) {
                        Checklist::query()->where('employee_id', $employee->id)->where('item_id', $itemId)->delete();
                    }

                    continue;
                }

                $deliveredId = (int) ($row['delivered_id'] ?? 0) ?: $actorEmployeeId;
                $receiverId = (int) ($row['receiver_id'] ?? 0) ?: null;

                // With no employee record of their own, the person saving cannot be the fallback deliverer.
                if ($deliveredId === null) {
                    throw ValidationException::withMessages(["rows.{$index}.delivered_id" => __('hr_checklists.employee.choose_deliverer')]);
                }
                $fullyReturned = $existing?->returned_status === ChecklistWorkflow::RECEIVED;

                if ($existing === null || $fullyReturned) {
                    $checklist = new Checklist;
                    $checklist->forceFill([
                        'item_id' => $itemId,
                        'employee_id' => $employee->id,
                        'delivered_id' => $deliveredId,
                        'delivered_status' => ChecklistWorkflow::PENDING,
                        'delivered_at' => null,
                        'receiver_id' => null,
                        'returned_status' => null,
                        'returned_at' => null,
                        'notes' => null,
                    ])->save();
                } else {
                    $values = ['delivered_id' => $deliveredId];

                    if ($receiverId !== null) {
                        $values['receiver_id'] = $receiverId;

                        if ($existing->returned_status !== ChecklistWorkflow::PENDING) {
                            $values['returned_status'] = ChecklistWorkflow::PENDING;
                        }
                    } else {
                        $values['receiver_id'] = null;
                        $values['returned_status'] = null;
                        $values['returned_at'] = null;
                    }

                    $existing->forceFill($values)->save();
                    $checklist = $existing;
                }

                $newForDelivery = ($originalDeliveredId === null
                        || ($checklist->delivered_id !== $originalDeliveredId && $checklist->delivered_status === ChecklistWorkflow::PENDING))
                    && $checklist->delivered_id !== $actorEmployeeId;

                if ($newForDelivery && $this->notifier->assigned($checklist, ChecklistNotifier::DELIVER)) {
                    $notified++;
                }

                $newForReturn = $checklist->receiver_id !== null
                    && $checklist->returned_status === ChecklistWorkflow::PENDING
                    && $checklist->receiver_id !== $originalReceiverId
                    && $checklist->receiver_id !== $actorEmployeeId;

                if ($newForReturn && $this->notifier->assigned($checklist, ChecklistNotifier::RECEIVE)) {
                    $notified++;
                }
            }

            return $notified;
        });
    }
}
