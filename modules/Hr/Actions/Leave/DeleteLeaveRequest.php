<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Leave;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Support\Leave\LeaveContext;
use Modules\Hr\Support\Leave\LeaveRequestPricing;

/**
 * Withdraw (owner, while pending) or delete (whoever manages it) a leave
 * request. Deleting an accepted request gives its deducted hours back to the
 * balance in the same transaction, so `used` keeps reconciling.
 */
final class DeleteLeaveRequest
{
    public function __construct(private readonly LeaveRequestPricing $pricing) {}

    public function handle(User $actor, int $leaveRequestId): void
    {
        DB::transaction(function () use ($actor, $leaveRequestId): void {
            $leaveRequest = LeaveContext::request($leaveRequestId, lock: true);
            Gate::forUser($actor)->authorize('delete', $leaveRequest);

            if ((int) $leaveRequest->status === LeaveRequestStatus::Accepted->value) {
                $hours = $this->pricing->deductionHours($leaveRequest);
                $balance = LeaveBalance::query()->lockForUpdate()->find($leaveRequest->leave_balance_id);

                if ($balance !== null && $hours > 0) {
                    $balance->update(['used' => max(0, round((float) $balance->used - $hours, 2))]);
                }
            }

            $leaveRequest->notes()->delete();
            $leaveRequest->delete();
        });
    }
}
