<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Livewire\Component;

class EmployeeProfile extends Component
{

    public Employee $employee;
    public function render()
    {
        $employee = $this->employee->load('account');
        return view('livewire.employee.employee-profile', compact(['employee']));
    }
}
