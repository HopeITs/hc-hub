<?php

declare(strict_types=1);

namespace Modules\Tracking\Models\Scopes;

use App\Support\EmployeeLookup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\TaskKind;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\Task;
use Modules\Tracking\Support\TrackingDuties;

/**
 * Row-level visibility for tasks.
 *
 * While a department-scoped user (see User::isTaskDepartmentScoped()) is
 * signed in, every Task query — lists, relations, counts and route binding —
 * only returns tasks they created, tasks they are assigned to, tasks of their
 * own HR department (an assignee or the creator works there, or the task is
 * handed to it through tasks.department_id), and tasks raised on a project or
 * program they answer for. Holders of proposals.update also see every
 * department task (tasks.department_id set) on a proposal's plan; tasks
 * without a department gain no viewer from that (contracts.md §12.2).
 *
 * Financial tasks (TaskKind::Financial) are Finance only: whatever else
 * matches, they stay hidden unless the user holds the Financial role or
 * proposals.update. Jobs and console commands have no signed-in user and are
 * untouched, as are users holding a bypass role.
 *
 * A task raised on an employee (source `employee`) is private to that
 * employee's file for everyone, bypass roles included: only its creator, its
 * assignees, the employee, and users who may view that employee record
 * (EmployeePolicy::view) see it. A task raised on a department (source
 * `department`) is also seen by that department's members.
 *
 * Organisation-wide figures and integrity guards opt out explicitly with
 * withoutGlobalScope(TaskVisibilityScope::class).
 *
 * @implements Scope<Task>
 */
class TaskVisibilityScope implements Scope
{
    /**
     * The role that sees financial tasks without proposal rights.
     */
    private const FinanceRole = 'Financial';

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            self::constrain($builder, $user);
        }
    }

    /**
     * Limit a tasks query to what this user may see; a no-op for users who
     * are not department-scoped.
     *
     * @param  Builder<covariant Task>  $query
     */
    public static function constrain(Builder $query, User $user): void
    {
        self::constrainEmployeeSubjects($query, $user);

        if (! $user->isTaskDepartmentScoped()) {
            return;
        }

        $departmentId = $user->departmentId();
        $departmentEmployeeIds = $user->departmentEmployeeIds();
        $ownedProjectIds = TrackingDuties::for($user)->ownedProjectIds();
        $managedProgramIds = $user->managedProgramIds();
        $isProposalEditor = $user->can('proposals.update');
        $seesFinancialTasks = $isProposalEditor || $user->hasRole(self::FinanceRole);
        $createdBy = $query->qualifyColumn('created_by');
        $sourceType = $query->qualifyColumn('source_type');
        $sourceId = $query->qualifyColumn('source_id');
        $taskDepartmentId = $query->qualifyColumn('department_id');

        $query->where(function (Builder $query) use ($user, $departmentId, $departmentEmployeeIds, $ownedProjectIds, $managedProgramIds, $isProposalEditor, $createdBy, $sourceType, $sourceId, $taskDepartmentId): void {
            $query->where($createdBy, $user->id);

            if ($departmentId !== null) {
                $query->orWhere($taskDepartmentId, $departmentId);
            }

            if ($isProposalEditor) {
                $query->orWhereNotNull($taskDepartmentId);
            }

            if ($departmentId !== null) {
                $query->orWhere(fn (Builder $query) => $query->where($sourceType, 'department')->where($sourceId, $departmentId));
            }

            $query->orWhere(fn (Builder $query) => self::onViewableEmployee($query, $user));

            if ($user->employee_id !== null) {
                $query->orWhereHas('taskAssignees', fn (Builder $assignees) => $assignees->where('employee_id', $user->employee_id));
            }

            if ($departmentEmployeeIds !== []) {
                $query->orWhereHas('taskAssignees', fn (Builder $assignees) => $assignees->whereIn('employee_id', $departmentEmployeeIds))
                    ->orWhereIn($createdBy, User::query()->select('id')->whereIn('employee_id', $departmentEmployeeIds));
            }

            if ($ownedProjectIds !== []) {
                $query->orWhere(fn (Builder $query) => $query->where($sourceType, 'project')->whereIn($sourceId, $ownedProjectIds))
                    ->orWhere(fn (Builder $query) => $query->where($sourceType, 'milestone')->whereIn($sourceId, Milestone::query()->select('id')->whereIn('project_id', $ownedProjectIds)));
            }

            if ($managedProgramIds !== []) {
                $query->orWhere(fn (Builder $query) => $query->where($sourceType, 'program')->whereIn($sourceId, $managedProgramIds));
            }
        });

        if (! $seesFinancialTasks) {
            $query->where($query->qualifyColumn('kind'), '!=', TaskKind::Financial->value);
        }
    }

    /**
     * Whether this user may see one task, by exactly the rule constrain()
     * applies to queries.
     */
    public static function allows(User $user, Task $task): bool
    {
        if (! $user->isTaskDepartmentScoped() && $task->source_type !== 'employee') {
            return true;
        }

        $query = Task::query()->withoutGlobalScope(self::class)->whereKey($task->getKey());

        self::constrain($query, $user);

        return $query->exists();
    }

    /**
     * Tasks on an employee leave a user's view unless the user raised them,
     * is assigned to them, or may view that employee.
     *
     * @param  Builder<covariant Task>  $query
     */
    private static function constrainEmployeeSubjects(Builder $query, User $user): void
    {
        $query->where(fn (Builder $query) => $query
            ->where($query->qualifyColumn('source_type'), '!=', 'employee')
            ->orWhere($query->qualifyColumn('created_by'), $user->id)
            ->orWhereHas('taskAssignees', fn (Builder $assignees) => $assignees->where('employee_id', $user->employee_id ?? 0))
            ->orWhere(fn (Builder $query) => self::onViewableEmployee($query, $user)));
    }

    /**
     * Narrows a tasks query to those raised on an employee the user may view:
     * their own record, or any record in the cities their HR access covers
     * when they hold employees.view.
     *
     * @param  Builder<covariant Task>  $query
     */
    private static function onViewableEmployee(Builder $query, User $user): void
    {
        $sourceId = $query->qualifyColumn('source_id');

        $query->where($query->qualifyColumn('source_type'), 'employee')
            ->where(function (Builder $query) use ($user, $sourceId): void {
                $query->where($sourceId, $user->employee_id ?? 0);

                if (! $user->can('employees.view')) {
                    return;
                }

                $reach = app(EmployeeLookup::class)->idsInHrReachOf($user);

                $reach === null
                    ? $query->orWhereNotNull($sourceId)
                    : $query->orWhereIn($sourceId, $reach);
            });
    }
}
