<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Leave\LeaveDecisionAuthority;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * Leave requests (C3). The owner edits and withdraws while pending; whoever
 * manages the request (LeaveRequest::manageableBy) sees, edits and deletes
 * it; only the direct manager decides, HR on their behalf while they are on
 * leave or when there is none (LeaveDecisionAuthority); HR in scope marks a
 * decision as seen or objects to it.
 *
 * Callers load leaveBalance.contract before asking (strict mode).
 */
class LeaveRequestPolicy
{
    /** The team list: managers of a position and HR. */
    public function viewAny(User $user): bool
    {
        return $user->can('leaves.view-all')
            || app(LeaveDecisionAuthority::class)->holdsManagerPosition($user);
    }

    /** Filing a request for someone else (HR, in its governorates). */
    public function createForOthers(User $user): bool
    {
        return $user->can('leaves.create') && HrAccess::for($user)->isHr();
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $this->owns($user, $leaveRequest) || $this->canManage($user, $leaveRequest);
    }

    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($this->owns($user, $leaveRequest)) {
            return $leaveRequest->status === LeaveRequestStatus::Pending->value && $user->can('leaves.update');
        }

        return $user->can('leaves.update') && $this->canManage($user, $leaveRequest);
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($this->owns($user, $leaveRequest)) {
            return $leaveRequest->status === LeaveRequestStatus::Pending->value;
        }

        return $user->can('leaves.delete') && $this->canManage($user, $leaveRequest);
    }

    /**
     * Only the direct manager decides; HR only on their behalf while they are
     * on leave, or when there is no direct manager (C3, DEC-05 revision, C3-Q1).
     */
    public function decide(User $user, LeaveRequest $leaveRequest, ?LeaveDecisionAuthority $authority = null): bool
    {
        return $leaveRequest->status === LeaveRequestStatus::Pending->value
            && ($authority ?? app(LeaveDecisionAuthority::class))->basisFor($user, $leaveRequest) !== null;
    }

    /** HR sends a decided request back to the manager as pending, with a note (C3 G3). */
    public function object(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->status !== LeaveRequestStatus::Pending->value
            && HrAccess::for($user)->isHr()
            && $user->can('leaves.review')
            && $this->canManage($user, $leaveRequest);
    }

    /** "Seen by HR". Nobody marks their own decision as seen (N-07). */
    public function review(User $user, LeaveRequest $leaveRequest, ?LeaveDecisionAuthority $authority = null): bool
    {
        return $leaveRequest->status !== LeaveRequestStatus::Pending->value
            && ! $leaveRequest->hr_approved
            && ! ($user->employee_id !== null && (int) $leaveRequest->approved_by === (int) $user->employee_id)
            && HrAccess::for($user)->isHr()
            && $user->can('leaves.review')
            && ($authority ? $authority->isManageableBy($user, $leaveRequest) : $this->canManage($user, $leaveRequest));
    }

    private function owns(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->employee_id !== null
            && (int) $user->employee_id === (int) $leaveRequest->leaveBalance?->contract?->employee_id;
    }

    private function canManage(User $user, LeaveRequest $leaveRequest): bool
    {
        return LeaveRequest::query()->manageableBy($user)->whereKey($leaveRequest->getKey())->exists();
    }
}
