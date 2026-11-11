<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Checklists;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\DefaultDeliverable;
use Modules\Hr\Models\Employee;

/**
 * The only place checklist delivery/return state may change.
 *
 * Every transition shares one shape:
 *
 *   - the row is located *through* the relationship that authorizes it
 *     (assignee for delivery, receiver for return, the edited employee for
 *     the HR workflow), so a foreign id simply does not match;
 *   - the expected source status is part of that same query, so a transition
 *     cannot skip a step or run twice;
 *   - the lookup happens inside a transaction with lockForUpdate, so two
 *     concurrent submissions serialize and the loser matches nothing;
 *   - the actor, the record and both states are written to the audit trail.
 */
final class ChecklistWorkflow
{
    public const string PENDING = 'pending';

    public const string DELIVERED = 'delivered';

    public const string RECEIVED = 'received';

    /**
     * The work inbox's batch: each item is still confirmed one by one, and
     * its audit entry carries the batch id. Null outside a batch.
     */
    private ?string $batchId = null;

    public function __construct(private readonly ChecklistNotifier $notifier) {}

    /**
     * The assignee confirms they handed the item over.
     */
    public function markDelivered(int $checklistId, int $actorEmployeeId, ?string $notes, ?string $batchId = null): Checklist
    {
        $this->batchId = $batchId;

        return $this->transition(
            fn (Builder $query): Builder => $query
                ->where('delivered_id', $actorEmployeeId)
                ->where('delivered_status', self::PENDING),
            $checklistId,
            [
                'delivered_status' => self::DELIVERED,
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
    public function markReceived(int $checklistId, int $actorEmployeeId, ?string $notes, ?string $batchId = null): Checklist
    {
        $this->batchId = $batchId;

        return $this->transition(
            fn (Builder $query): Builder => $query
                ->where('receiver_id', $actorEmployeeId)
                ->where('returned_status', self::PENDING),
            $checklistId,
            [
                'returned_status' => self::RECEIVED,
                'returned_at' => now(),
                'notes' => $notes,
            ],
            'returned_status',
            'checklist_received',
        );
    }

    /**
     * HR starts a return cycle from the employee's record. Scoped to that
     * employee, so the checklist id cannot be swapped for another person's.
     */
    public function initiateReturn(int $checklistId, Employee $employee, int $receiverId): Checklist
    {
        return $this->transition(
            fn (Builder $query): Builder => $query
                ->where('employee_id', $employee->id)
                ->where('delivered_status', self::DELIVERED)
                // Not whereNot(): "NOT (returned_status = 'received')" is NULL
                // for a row that has never been through a return cycle.
                ->where(fn (Builder $q) => $q
                    ->whereNull('returned_status')
                    ->orWhere('returned_status', '!=', self::RECEIVED)),
            $checklistId,
            [
                'receiver_id' => $receiverId,
                'returned_status' => self::PENDING,
                'returned_at' => now(),
            ],
            'returned_status',
            'checklist_return_initiated',
        );
    }

    /**
     * A volunteer's contract just stopped renewing: start the return cycle
     * for whatever of their equipment is still out, and remind whoever is
     * meant to collect it. Only delivered items with no return cycle yet are
     * touched; the receiver is the governorate's default deliverer for the
     * item, and an item with none is left for HR to assign by hand.
     */
    public function requestBelongingsReturn(Employee $employee): void
    {
        $checklists = Checklist::query()
            ->where('employee_id', $employee->id)
            ->where('delivered_status', self::DELIVERED)
            ->whereNull('returned_status')
            ->get(['id', 'item_id']);

        if ($checklists->isEmpty()) {
            return;
        }

        $defaultReceivers = DefaultDeliverable::query()
            ->where('city_id', $employee->city_id)
            ->pluck('employee_id', 'item_id');

        foreach ($checklists as $checklist) {
            $receiverId = $defaultReceivers->get($checklist->item_id);

            if (! $receiverId) {
                continue;
            }

            $updated = $this->initiateReturn($checklist->id, $employee, (int) $receiverId);

            $this->notifier->assigned($updated, ChecklistNotifier::RECEIVE);
        }
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
                ->where('returned_status', self::RECEIVED)
                ->lockForUpdate()
                ->first();

            abort_unless($checklist !== null, Response::HTTP_NOT_FOUND);

            $this->record($checklist, 'checklist_finalized', 'returned_status', self::RECEIVED, null);

            $checklist->delete();
        });
    }

    /**
     * @param  callable(Builder<Checklist>): Builder<Checklist>  $scope  the authorization + expected-state filter
     * @param  array<string, mixed>  $attributes
     */
    private function transition(callable $scope, int $checklistId, array $attributes, string $stateColumn, string $event): Checklist
    {
        return DB::transaction(function () use ($scope, $checklistId, $attributes, $stateColumn, $event): Checklist {
            $checklist = $scope(Checklist::query()->whereKey($checklistId))
                ->lockForUpdate()
                ->first();

            // Deliberately the same answer as "no such checklist": a caller
            // probing ids learns nothing about records they cannot act on.
            abort_unless($checklist !== null, Response::HTTP_NOT_FOUND);

            $from = $checklist->getAttribute($stateColumn);

            $checklist->update($attributes);

            $this->record($checklist, $event, $stateColumn, $from, $checklist->getAttribute($stateColumn));

            return $checklist;
        });
    }

    /**
     * The audit entry of one transition.
     *
     * STUB: the old app wrote this to the `checklist` activity log. The hub's
     * activity recorder needs a morph key for Checklist in
     * AppServiceProvider::configureMorphMap() (a shared file), so until that
     * is added the entry goes to the application log.
     */
    private function record(Checklist $checklist, string $event, string $column, ?string $from, ?string $to): void
    {
        Log::info('hr.checklist.'.$event, [
            'checklist_id' => $checklist->id,
            'actor_user_id' => Auth::id(),
            'transition' => $column,
            'from' => $from,
            'to' => $to,
            'employee_id' => $checklist->employee_id,
            'item_id' => $checklist->item_id,
        ] + ($this->batchId !== null ? ['batch_id' => $this->batchId] : []));

        $this->batchId = null;
    }
}
