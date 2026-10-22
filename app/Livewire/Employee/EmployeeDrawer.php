<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * B4 read-only employee detail drawer, opened from the employee list.
 *
 * It lives beside the table (a separate component), so opening and closing it never
 * re-renders or touches the table's search, filters, sort or page (AC-B4-01).
 * Not an editable dialog, so GuardsModalClose is deliberately not used.
 *
 * Only fields the active role already sees in the list or on the profile are shown;
 * never pay, dates of birth or private documents.
 */
class EmployeeDrawer extends Component
{
    public ?int $employeeId = null;

    /** Re-authorized on every call: the id comes from the browser. */
    public function open(int $id): void
    {
        $user = auth()->user();
        abort_unless($user?->hasActivePermission('employees.view'), 403);

        $employee = Employee::query()
            ->when($user->activeCityIds(), fn (Builder $q, array $cityIds) => $q->whereIn('city_id', $cityIds))
            ->findOrFail($id);

        // HR record ability in the active city scope, and the directory tier
        // (a former employee is HR / general manager only; denied as not found).
        Gate::authorize('view', $employee);
        Gate::authorize('viewDirectory', $employee);

        $this->employeeId = $employee->id;
    }

    public function close(): void
    {
        $this->employeeId = null;
    }

    public function render()
    {
        $employee = null;

        if ($this->employeeId !== null) {
            $employee = Employee::query()
                ->with(['city', 'account', 'media', 'contracts.position.directManager.activeContracts'])
                ->find($this->employeeId);

            // Authorization can change between open and a later re-render (role switch).
            if ($employee === null || ! $this->stillAllowed($employee)) {
                $this->employeeId = null;
                $employee = null;
            }
        }

        return view('livewire.employee.drawer', ['employee' => $employee]);
    }

    private function stillAllowed(Employee $employee): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->hasActivePermission('employees.view')
            && Gate::forUser($user)->allows('view', $employee)
            && Gate::forUser($user)->allows('viewDirectory', $employee);
    }
}
