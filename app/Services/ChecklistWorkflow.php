<?php

namespace App\Services;

use App\Models\Checklist;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\Activity;

/**
 * The only place checklist delivery/return state may change.
 *
 * The components previously resolved the record with a bare
 * Checklist::findOrFail($id) and then wrote to it. The list *above* the buttons
 * was correctly scoped to the signed-in employee, which made the flaw easy to
 * miss: the read was safe, the write was global. Anyone able to reach the page
 * could post any checklist id and mark another employee's equipment delivered,
 * returned, or deleted.
 *
 * Every transition here shares one shape:
 *
 *   - the row is located *through* the relationship that authorizes it
 *     (assignee for delivery, receiver for return, edited employee for the HR
 *     workflow), so a foreign id simply does not match;
 *   - the expected source status is part of that same query, so a transition
 *     cannot skip a step or run twice;
 *   - the lookup happens inside a transaction with lockForUpdate, so two
 *     concurrent submissions serialize and the loser matches nothing;
 *   - the actor, the record, and both states are written to the audit log.
 */
class ChecklistWorkflow
{
    /**
     * The assignee confirms they handed the item over.
     */
    public function markDelivered(int $checklistId, int $actorEmployeeId, ?string $notes): Checklist
    {
        return $this->transition(
            fn (Builder $query) => $query
                ->where('delivered_id', $actorEmployeeId)
                ->where('delivered_status', 'pending'),
            $checklistId,
            [
                'delivered_status' => 'delivered',
                'delivered_at' => now(),
                'notes' => $notes,
            ],
            'delivered_status',
            'checklist_delivered',
        );
    }

    /**
     * The nominated receiver confirms the item came back.
     */
    public function markReceived(int $checklistId, int $actorEmployeeId, ?string $notes): Checklist
    {
        return $this->transition(
            fn (Builder $query) => $query
                ->where('receiver_id', $actorEmployeeId)
                ->where('returned_status', 'pending'),
            $checklistId,
            [
                'returned_status' => 'received',
                'returned_at' => now(),
                'notes' => $notes,
            ],
            'returned_status',
            'checklist_received',
        );
    }

    /**
     * HR starts a return cycle from the employee's edit screen.
     *
     * Scoped to the employee actually being edited, so the checklist id in the
     * payload cannot be swapped for one belonging to a different employee — or
     * a different city, since reaching that edit screen is already gated by
     * EmployeePolicy::update.
     */
    public function initiateReturn(int $checklistId, Employee $employee, int $receiverId): Checklist
    {
        return $this->transition(
            fn (Builder $query) => $query
                ->where('employee_id', $employee->id)
                ->where('delivered_status', 'delivered')
                ->whereNot('returned_status', 'received'),
            $checklistId,
            [
                'receiver_id' => $receiverId,
                'returned_status' => 'pending',
                'returned_at' => now(),
            ],
            'returned_status',
            'checklist_return_initiated',
        );
    }

    /**
     * HR closes a completed cycle, removing the assignment record.
     */
    public function finalizeReturn(int $checklistId, Employee $employee): void
    {
        DB::transaction(function () use ($checklistId, $employee): void {
            $checklist = Checklist::query()
                ->whereKey($checklistId)
                ->where('employee_id', $employee->id)
                ->where('returned_status', 'received')
                ->lockForUpdate()
                ->first();

            abort_unless($checklist, Response::HTTP_NOT_FOUND);

            $this->record($checklist, 'checklist_finalized', 'returned_status', 'received', null);

            $checklist->delete();
        });
    }

    /**
     * @param  callable(Builder): Builder  $scope  the authorization + expected-state filter
     */
    private function transition(
        callable $scope,
        int $checklistId,
        array $attributes,
        string $stateColumn,
        string $event,
    ): Checklist {
        return DB::transaction(function () use ($scope, $checklistId, $attributes, $stateColumn, $event): Checklist {
            $checklist = $scope(Checklist::query()->whereKey($checklistId))
                ->lockForUpdate()
                ->first();

            // Deliberately indistinguishable from "no such checklist": a
            // caller probing ids learns nothing about records they cannot act
            // on, nor about which state a foreign record is in.
            abort_unless($checklist, Response::HTTP_NOT_FOUND);

            $from = $checklist->{$stateColumn};

            $checklist->update($attributes);

            $this->record($checklist, $event, $stateColumn, $from, $checklist->{$stateColumn});

            return $checklist;
        });
    }

    private function record(Checklist $checklist, string $event, string $column, ?string $from, ?string $to): void
    {
        Activity::useLog('checklist')
            ->performedOn($checklist)
            ->withProperties([
                'transition' => $column,
                'from' => $from,
                'to' => $to,
                'employee_id' => $checklist->employee_id,
                'item_id' => $checklist->item_id,
            ])
            ->log($event);
    }
}
