<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class EmployeeEdit extends Component
{
    public Employee $employee;
    public array $item = [];

    public function mount(Employee $employee)
    {
        $this->employee = $employee;
        $this->item = $employee->toArray();
        $this->item['name'] = $employee->getTranslations('name');
        $this->item['father_name'] = $employee->getTranslations('father_name');
        $this->item['mother_name'] = $employee->getTranslations('mother_name');
        $this->item['address'] = $employee->getTranslations('address');

        $this->item['is_board_member'] = (bool) $employee->is_board_member;
    }

    protected function rules()
    {
        return [
            'item.name.*' => 'required|string|max:255',
            'item.national_number' => 'required|string|unique:employees,national_number,' . $this->employee->id,
            'item.actual_job' => 'nullable|string|max:255',
            'item.years_of_service' => 'nullable|numeric|min:0',
            'item.work_email' => 'nullable|email|unique:employees,work_email,' . $this->employee->id,
            'item.direct_manager_id' => 'nullable|exists:employees,id',
            'item.tech_manager_id' => 'nullable|exists:employees,id',
            'item.dob' => 'nullable|date',
            'item.actual_dob' => 'nullable|date',
            'item.civil_registry_office' => 'nullable|string|max:255',
            'item.civil_registry_number' => 'nullable|string|max:255',
            'item.place_of_registration' => 'nullable|string|max:255',
            'item.gender' => 'nullable|in:0,1,2',
            'item.father_name.*' => 'nullable|string|max:255',
            'item.mother_name.*' => 'nullable|string|max:255',
            'item.nationality' => 'required|in:syrian',
            'item.marital_status' => 'nullable|in:0,1,2',
            'item.children' => 'nullable|integer|min:0',
            'item.work_phone' => 'nullable|string|max:255',
            'item.personal_phone' => 'nullable|string|max:255',
            'item.personal_email' => 'nullable|email',
            'item.address.*' => 'nullable|string|max:255',
            'item.skills' => 'nullable|string',
            'item.interests' => 'nullable|string',
            'item.is_active' => 'required|boolean',
            'item.is_board_member' => 'required|boolean',
            'item.board_member_started_at' => 'nullable|required_if:item.is_board_member,true|date',
            'item.board_member_end_at' => 'nullable|date|after_or_equal:item.board_member_started_at',
        ];
    }

    public function save()
    {
        $this->validate();
        $this->employee->update($this->item);
        LivewireAlert::title(__('general.success-message'))->success()->show();
        return redirect()->route('employees.index');
    }

    public function render()
    {
        return view('livewire.employee.employee-edit', [
            'allEmployees' => Employee::select('id', 'name')->where('id', '!=', $this->employee->id)->get(),
        ]);
    }
}
