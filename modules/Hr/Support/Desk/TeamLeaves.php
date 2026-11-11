<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveRequest;

/**
 * The leave requests of a set of employees that touch a span of days, read
 * once with everything a Desk card prints (the person, the leave type).
 */
final class TeamLeaves
{
    /**
     * The most leaves one card reads.
     */
    public const int Limit = 60;

    /**
     * @param  list<int>  $employeeIds
     * @param  list<LeaveRequestStatus>  $statuses
     * @return Collection<int, LeaveRequest>
     */
    public function between(array $employeeIds, CarbonInterface $from, CarbonInterface $to, array $statuses = [LeaveRequestStatus::Accepted]): Collection
    {
        if ($employeeIds === []) {
            return new Collection;
        }

        return LeaveRequest::query()
            ->select(['id', 'leave_balance_id', 'type', 'start_at', 'end_at', 'status', 'created_at'])
            ->whereIn('status', array_map(fn (LeaveRequestStatus $status): int => $status->value, $statuses))
            ->where('start_at', '<=', $to->copy()->endOfDay())
            ->where('end_at', '>=', $from->copy()->startOfDay())
            ->whereHas('leaveBalance.contract', fn (Builder $contract) => $contract->whereIn('employee_id', $employeeIds))
            ->with([
                'leaveBalance:id,contract_id,type_id',
                'leaveBalance.leaveType:id,name_en,name_ar',
                'leaveBalance.contract:id,employee_id',
                'leaveBalance.contract.employee:'.implode(',', Employee::NameColumns),
            ])
            ->orderBy('start_at')
            ->limit(self::Limit)
            ->get();
    }

    /**
     * The employee a leave belongs to, or null for a broken chain.
     */
    public static function employeeOf(LeaveRequest $leave): ?Employee
    {
        return $leave->leaveBalance?->contract?->employee;
    }
}
