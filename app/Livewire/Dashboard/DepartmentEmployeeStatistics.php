<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Employee;

class DepartmentEmployeeStatistics extends Component
{
    public $departmentLabels = [];
    public $employeeCounts = [];

    public function mount()
    {
        $employees = Employee::query()
            ->with(['contracts.position.department'])
            ->get();

        $departments = $employees
            ->map(function ($employee) {
                $contract = $employee->contracts->sortByDesc('start_date')->first();
                return optional($contract?->position?->department)->code;
            })
            ->filter()
            ->countBy()
            ->sortKeys()
            ->toArray();

        $this->departmentLabels = array_keys($departments);
        $this->employeeCounts = array_values($departments);
    }

    public function render()
    {
        return view('livewire.dashboard.department-employee-statistics');
    }
}
