<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Leave;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Leave\LeaveContext;
use Modules\Hr\Support\Leave\LeaveDecisionAlreadyMade;
use Modules\Hr\Support\Leave\LeaveDecisionAuthority;
use Modules\Hr\Support\Leave\LeaveNotificationService;
use Modules\Hr\Support\Leave\LeaveRequestPricing;

/**
 * Accept or reject a pending leave request (the old submitDecision()). The
 * row is locked, then authorized, so a concurrent decision sees the committed
 * status and is told it was already decided (LeaveDecisionAlreadyMade). The
 * basis (manager, on behalf, no manager) is stored with the decision.
 * Accepting deducts the request's snapshot from the balance and may not take
 * an annual balance below zero; rejecting needs a reason.
 */
final class DecideLeaveRequest
{
    private const EPSILON = 0.005;

    public function __construct(
        private readonly LeaveDecisionAuthority $authority,
        private readonly LeaveRequestPricing $pricing,
        private readonly LeaveNotificationService $notifications,
    ) {}

    /**
     * @throws LeaveDecisionAlreadyMade
     * @throws ValidationException
     */
    public function handle(User $actor, int $leaveRequestId, mixed $status, ?string $rejectReason): LeaveRequest
    {
        $this->assertStillPending($actor, LeaveContext::request($leaveRequestId));

        $status = (int) $status;
        $rejectReason = trim((string) $rejectReason);

        Validator::make(
            ['status' => $status, 'reject_reason' => $rejectReason],
            [
                'status' => ['required', Rule::in([LeaveRequestStatus::Accepted->value, LeaveRequestStatus::Rejected->value])],
                'reject_reason' => [Rule::requiredIf($status === LeaveRequestStatus::Rejected->value), 'nullable', 'string', 'max:255'],
            ],
            [],
            ['status' => __('hr_leave.fields.decision'), 'reject_reason' => __('hr_leave.fields.reject_reason')],
        )->validate();

        $decided = DB::transaction(function () use ($actor, $leaveRequestId, $status, $rejectReason): LeaveRequest {
            $leaveRequest = LeaveContext::request($leaveRequestId, lock: true);
            $leaveRequest->leaveBalance->contract->loadMissing('position.directManager:id');

            $this->assertStillPending($actor, $leaveRequest);
            Gate::forUser($actor)->authorize('decide', $leaveRequest);

            $basis = $this->authority->basisFor($actor, $leaveRequest);
            $onBehalfOf = $basis === LeaveDecisionAuthority::ON_BEHALF
                ? $this->authority->directManagers($leaveRequest)->first()?->id
                : null;

            $decision = [
                'approved_at' => now(),
                'approved_by' => $actor->employee_id,
                'decision_basis' => $basis,
                'decided_on_behalf_of' => $onBehalfOf,
            ];

            if ($status === LeaveRequestStatus::Accepted->value) {
                // Lock order: request, then balance.
                LeaveBalance::query()->whereKey($leaveRequest->leave_balance_id)->lockForUpdate()->value('id');
                $balance = $leaveRequest->leaveBalance;
                $balance->forceFill(LeaveBalance::query()->whereKey($balance->id)->first(['deserved', 'used'])?->only(['deserved', 'used']) ?? [])->syncOriginal();

                $hours = $this->pricing->deductionHours($leaveRequest);
                $this->assertFitsBalance($balance, $hours);

                $leaveRequest->update(['status' => LeaveRequestStatus::Accepted->value, 'reject_reason' => null] + $decision);

                if ($hours > 0) {
                    $balance->increment('used', $hours);
                }
            } else {
                $leaveRequest->update(['status' => LeaveRequestStatus::Rejected->value, 'reject_reason' => $rejectReason] + $decision);
            }

            return $leaveRequest;
        });

        $this->notifications->sendDecision($decided, $actor);

        return $decided;
    }

    /**
     * A request decided since the dialog opened is reported as already
     * decided, not as a 403, to someone who may still see it.
     */
    private function assertStillPending(User $actor, LeaveRequest $leaveRequest): void
    {
        if ((int) $leaveRequest->status !== LeaveRequestStatus::Pending->value && Gate::forUser($actor)->allows('view', $leaveRequest)) {
            throw new LeaveDecisionAlreadyMade($leaveRequest);
        }
    }

    /** Accepting may not take an annual balance below zero (committed figures only). */
    private function assertFitsBalance(LeaveBalance $balance, float $hours): void
    {
        if ($hours <= 0 || ! $balance->isAnnual()) {
            return;
        }

        $available = round(LeaveRequestPricing::eligibleAnnualHours($balance) - (float) $balance->used, 2);

        if ($available - $hours < -self::EPSILON) {
            throw ValidationException::withMessages([
                'status' => __('hr_leave.quote.decision.exceeds_balance', [
                    'available' => round(max(0, $available), 2),
                    'requested' => round($hours, 2),
                ]),
            ]);
        }
    }
}
