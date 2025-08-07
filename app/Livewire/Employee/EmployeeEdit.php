<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use App\Livewire\Employee\Validators\EmployeeFormValidator;

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
        return EmployeeFormValidator::rules($this->employee->id);
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
