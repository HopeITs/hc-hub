<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Inbox;

use App\Support\Modules\Contracts\SubjectLinkSource;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveRequest;

/**
 * Where HR records lead from a notification or activity row
 * (App\Notifications\SubjectLinks).
 */
final class HrSubjectLinks implements SubjectLinkSource
{
    private const array Types = ['employee', 'contract', 'leave_request', 'position'];

    public function handles(string $type): bool
    {
        return in_array($type, self::Types, true);
    }

    public function routeFor(string $type, int $id): ?string
    {
        return match ($type) {
            'employee' => $this->employeeRoute($id),
            'contract' => route('hr.contracts.show', $id),
            'leave_request' => $this->leaveRequestRoute($id),
            'position' => route('hr.positions.index'),
            default => null,
        };
    }

    /**
     * The profile is bound by username; a record without one (a draft) leads
     * to the directory instead.
     */
    private function employeeRoute(int $id): ?string
    {
        $employee = Employee::query()->find($id, ['id', 'username']);

        if ($employee === null) {
            return null;
        }

        return blank($employee->username) ? route('hr.employees.index') : route('hr.employees.show', $employee);
    }

    /**
     * A leave request has no page of its own; it leads to the requester's profile.
     */
    private function leaveRequestRoute(int $id): ?string
    {
        $employeeId = LeaveRequest::query()
            ->whereKey($id)
            ->join('leave_balances', 'leave_balances.id', '=', 'leave_requests.leave_balance_id')
            ->join('contracts', 'contracts.id', '=', 'leave_balances.contract_id')
            ->value('contracts.employee_id');

        return $employeeId === null ? null : $this->employeeRoute((int) $employeeId);
    }
}
