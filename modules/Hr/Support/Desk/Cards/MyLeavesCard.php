<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\LeaveRequest;

/**
 * My leaves: the viewer's own requests, the ones still ahead first, each with
 * its decision.
 */
final class MyLeavesCard extends HrCard
{
    public function key(): string
    {
        return 'my-leaves';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null && $user->can('leaves.view-own');
    }

    public function register(): ?string
    {
        return 'hr.leave.requests.index';
    }

    public function icon(): string
    {
        return 'cal';
    }

    public function layout(): string
    {
        return 'agenda';
    }

    public function figures(User $user): array
    {
        $mine = $this->mine($user);

        return [
            $this->figure(__('hr_desk.cards.my-leaves.pending'), $mine->where('status', LeaveRequestStatus::Pending->value)->count(), 'warn'),
            $this->figure(__('hr_desk.cards.my-leaves.upcoming'), $mine->filter(fn (LeaveRequest $leave): bool => $leave->status === LeaveRequestStatus::Accepted->value && $leave->end_at?->isFuture() === true)->count(), 'active'),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->mine($user)->map(function (LeaveRequest $leave): array {
            $status = LeaveRequestStatus::tryFrom((int) $leave->status) ?? LeaveRequestStatus::Pending;
            $tone = match ($status) {
                LeaveRequestStatus::Accepted => 'good',
                LeaveRequestStatus::Rejected => 'critical',
                default => 'warn',
            };

            return $this->shaped(
                $this->row('ml-'.$leave->id, (string) ($leave->leaveBalance?->leaveType?->name ?? '—'), $leave->start_at === null ? '' : $this->span($leave->start_at, $leave->end_at), ['label' => (string) __('hr_desk.leave_status.'.$status->value), 'tone' => $tone], $this->link('hr.leave.requests.index')),
                lead: $leave->start_at === null ? null : $this->day($leave->start_at, $tone),
            );
        });
    }

    /**
     * The viewer's requests of the last three months and everything ahead.
     *
     * @return Collection<int, LeaveRequest>
     */
    private function mine(User $user): Collection
    {
        $employeeId = $this->viewer($user)->employeeId();

        if ($employeeId === null) {
            return new Collection;
        }

        return once(fn (): Collection => LeaveRequest::query()
            ->select(['id', 'leave_balance_id', 'start_at', 'end_at', 'status'])
            ->whereHas('leaveBalance.contract', fn (Builder $contract) => $contract->where('employee_id', $employeeId))
            ->where('end_at', '>=', now()->subMonths(3))
            ->with(['leaveBalance:id,type_id', 'leaveBalance.leaveType:id,name_en,name_ar'])
            ->orderByRaw('CASE WHEN end_at >= ? THEN 0 ELSE 1 END', [now()->startOfDay()])
            ->orderBy('start_at')
            ->limit(self::RowLimit)
            ->get());
    }
}
