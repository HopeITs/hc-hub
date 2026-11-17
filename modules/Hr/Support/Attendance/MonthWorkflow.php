<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Attendance;

use Carbon\CarbonImmutable;
use Modules\Core\Models\User;
use Modules\Hr\Models\AttendanceMonth;
use Modules\Hr\Models\Employee;

/**
 * The life of a person's month: the employee may confirm it, the manager (or HR) approves it,
 * which approves the overtime, and HR locks it. Nobody approves or decides their own month. A
 * change to an approved month (a decision, new punches) takes the approval back, so what was
 * approved is what is locked. A locked month does not change until HR reopens it.
 *
 * The HR app also adds the approved overtime to the annual leave balance when a month is locked;
 * the hub's leave module has no overtime credit yet, so locking here records the hours only.
 */
class MonthWorkflow
{
    public function __construct(
        private readonly Roster $roster,
        private readonly Timesheet $timesheet,
    ) {}

    /**
     * What this user may do with this person's month now.
     *
     * @return array{decide: bool, confirm: bool, approve: bool, lock: bool, reopen: bool, signed: bool}
     */
    public function abilities(User $user, Employee $employee, CarbonImmutable $month, AttendanceMonth $record, int $toDecide, ?bool $covers = null): array
    {
        $state = $record->state();
        $locked = $state === AttendanceMonth::LOCKED;
        $covers ??= $this->roster->covers($user, $employee, $month);
        $own = $user->employee_id !== null && (int) $user->employee_id === (int) $employee->id;
        $supervises = $covers && ! $own && $this->roster->mode($user) !== Roster::OWN;
        $manages = $covers && $user->can('attendance.manage');
        $ready = $toDecide === 0;
        $needsConfirmation = (bool) config('attendance.require_employee_confirmation');

        return [
            'decide' => $supervises && ! $locked,
            'confirm' => $own && $user->can('attendance.view-own') && $state === AttendanceMonth::OPEN && $ready,
            'approve' => $supervises && $ready
                && ($needsConfirmation ? $state === AttendanceMonth::CONFIRMED : in_array($state, [AttendanceMonth::OPEN, AttendanceMonth::CONFIRMED], true)),
            'lock' => $manages && $state === AttendanceMonth::APPROVED,
            'reopen' => $manages && $state !== AttendanceMonth::OPEN,
            'signed' => $manages,
        ];
    }

    public function confirm(User $user, Employee $employee, CarbonImmutable $month): AttendanceMonth
    {
        $record = $this->allowed('confirm', $user, $employee, $month);

        $record->forceFill(['employee_confirmed_at' => now(), 'employee_confirmed_by' => $user->id])->save();

        return $record;
    }

    public function approve(User $user, Employee $employee, CarbonImmutable $month): AttendanceMonth
    {
        $record = $this->allowed('approve', $user, $employee, $month);
        $totals = $this->timesheet->forEmployee($employee, $month)['totals'];

        $record->forceFill([
            'approved_at' => now(),
            'approved_by' => $user->id,
            'overtime_hours_approved' => $totals['overtime_hours'],
        ])->save();

        return $record;
    }

    public function lock(User $user, Employee $employee, CarbonImmutable $month): AttendanceMonth
    {
        $record = $this->allowed('lock', $user, $employee, $month);

        $record->forceFill(['locked_at' => now(), 'locked_by' => $user->id])->save();

        return $record;
    }

    public function reopen(User $user, Employee $employee, CarbonImmutable $month): AttendanceMonth
    {
        $record = $this->allowed('reopen', $user, $employee, $month);

        $record->forceFill([
            'employee_confirmed_at' => null, 'employee_confirmed_by' => null,
            'approved_at' => null, 'approved_by' => null, 'overtime_hours_approved' => 0,
            'locked_at' => null, 'locked_by' => null,
        ])->save();

        return $record;
    }

    /** A decision or new punches changed an approved month: the approval no longer holds. */
    public function withdrawApproval(Employee|int $employee, CarbonImmutable $month): void
    {
        AttendanceMonth::query()
            ->where('employee_id', $employee instanceof Employee ? $employee->id : $employee)
            ->whereDate('month', $month->startOfMonth()->toDateString())
            ->whereNotNull('approved_at')
            ->whereNull('locked_at')
            ->update([
                'employee_confirmed_at' => null, 'employee_confirmed_by' => null,
                'approved_at' => null, 'approved_by' => null, 'overtime_hours_approved' => 0,
            ]);
    }

    public function isLocked(Employee|int $employee, CarbonImmutable $month): bool
    {
        return AttendanceMonth::query()
            ->where('employee_id', $employee instanceof Employee ? $employee->id : $employee)
            ->whereDate('month', $month->startOfMonth()->toDateString())
            ->whereNotNull('locked_at')
            ->exists();
    }

    private function allowed(string $ability, User $user, Employee $employee, CarbonImmutable $month): AttendanceMonth
    {
        $record = AttendanceMonth::for($employee, $month);
        $toDecide = $this->timesheet->forEmployee($employee, $month)['totals']['to_decide'];

        if (! ($this->abilities($user, $employee, $month, $record, $toDecide)[$ability] ?? false)) {
            // Name the reason a person can act on: days still waiting come before "not allowed".
            throw WorkflowRefused::because($toDecide > 0 && in_array($ability, ['confirm', 'approve'], true) ? 'days-waiting' : $ability);
        }

        return $record;
    }
}
