<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Livewire\Component;

class EmployeeEditForm extends Component
{
    public $item = [];

    public function edit(Employee $employee)
{
    $this->item = [
        'id' => $employee->id,
        'name' => $employee->getTranslation('name', app()->getLocale()),
        'dob' => $employee->dob,
        'years_of_service' => $employee->years_of_service,
        'national_number' => $employee->national_number,
        'civil_registry_office' => $employee->civil_registry_office,
        'civil_registry_number' => $employee->civil_registry_number,
        'place_of_registration' => $employee->place_of_registration,
        'gender' => $employee->gender,
        'father_name' => [
            app()->getLocale() => $employee->getTranslation('father_name', app()->getLocale())
        ],
        'mother_name' => [
            app()->getLocale() => $employee->getTranslation('mother_name', app()->getLocale())
        ],
        'nationality' => $employee->nationality,
        'marital_status' => $employee->marital_status,
        'children' => $employee->children,
        'work_phone' => $employee->work_phone,
        'personal_phone' => $employee->personal_phone,
        'personal_email' => $employee->personal_email,
        'work_email' => $employee->work_email,
        'address' => [
            app()->getLocale() => $employee->getTranslation('address', app()->getLocale())
        ],
        'skills' => $employee->skills,
        'interests' => $employee->interests,
        'is_active' => $employee->is_active,
        'actual_job' => $employee->actual_job,
        'direct_manager_id' => $employee->direct_manager_id,
        'tech_manager_id' => $employee->tech_manager_id,
        'job_level_id' => $employee->job_level_id,
        'contract_type_id' => $employee->contract_type_id,
        'department_id' => $employee->department_id,
    ];
}

    public function save()
    {
        $this->validate([
            'item.full_name' => 'required|string|max:255',
            'item.work_email' => 'required|email',
        ]);

        Employee::findOrFail($this->item['id'])->update($this->item);

        session()->flash('message', __('general.success-message'));
        return redirect()->route('employees.index');
    }

    public function render()
    {
        return view('livewire.employee.employee-form');
    }
}
