<?php

namespace Modules\Hr\Services\Assistant\Drafts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Hr\Livewire\Employee\Leave\AllLeaveRequestModule;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;

/**
 * The All leaves page's own "Add" choices, read without the page.
 *
 * Never mounted or rendered: it only exposes the page's protected lists, so
 * the assistant offers exactly the employees, balances and alternatives the
 * page's dialog offers (scopedEmployees(): the HR role's cities; leavesFor();
 * alternativeEmployeesFor()). Each reads the requester from auth(), so the
 * caller runs it as the user (LeaveForEmployeeDrafter::asUser()).
 */
class LeaveForEmployeeScreen extends AllLeaveRequestModule
{
    /**
     * Who may file for an employee: the header's New entry and the page's own
     * gates (route active_permission:hr.leaves.view-all, create()).
     */
    public static function allows(User $user): bool
    {
        return $user->hasActivePermission('hr.leaves.create')
            && $user->hasActivePermission('hr.leaves.view-all')
            && $user->activeRoleIs('HR', 'super-admin');
    }

    /**
     * The page's employee dropdown for the signed-in user.
     *
     * @return Collection<int, Employee>
     */
    public function employees(): Collection
    {
        return self::typed($this->scopedEmployees(), Employee::class);
    }

    /** One employee of that dropdown, or null when the user may not file for them. */
    public function employee(mixed $id): ?Employee
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);

        return $id === false ? null : $this->employees()->first(fn (Employee $employee) => (int) $employee->id === $id);
    }

    /**
     * The page's leave-type dropdown for $employee.
     *
     * @return Collection<int, LeaveBalance>
     */
    public function balances(Employee $employee): Collection
    {
        return self::typed($this->leavesFor($employee), LeaveBalance::class);
    }

    /**
     * The page's alternative-employee dropdown for $employee.
     *
     * @return Collection<int, Employee>
     */
    public function alternatives(Employee $employee): Collection
    {
        return self::typed($this->alternativeEmployeesFor($employee), Employee::class);
    }

    /**
     * The page's list, typed: its records of $class, in order.
     *
     * @template TModel of Model
     *
     * @param  iterable<mixed>  $items
     * @param  class-string<TModel>  $class
     * @return Collection<int, TModel>
     */
    private static function typed(iterable $items, string $class): Collection
    {
        $typed = [];

        foreach ($items as $item) {
            if ($item instanceof $class) {
                $typed[] = $item;
            }
        }

        return collect($typed);
    }
}
