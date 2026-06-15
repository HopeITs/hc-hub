<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EmployeeProfile extends Component
{
    public Employee $employee;

    public function mount(?Employee $employee = null): void
    {
        $this->employee = $employee?->exists
            ? $employee
            : Auth::user()?->profile ?? abort(404);
    }

    public function render()
    {
        $employee = $this->employee->load('account');
        return view('livewire.employee.employee-profile', compact(['employee']));
    }
}
