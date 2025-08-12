<?php

namespace App\Livewire\Employee;

use App\Livewire\Forms\EmployeeForm;
use App\Models\ChecklistItem;
use App\Models\Employee;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeEdit extends Component
{
    use WithFileUploads;

    public EmployeeForm $form;

    public function mount(Employee $employee)
    {
        $this->form->setEmployee($employee);
    }

    public function save()
    {
        $this->form->update();
        LivewireAlert::title(__('general.success-message'))->success()->show();
        return redirect()->route('employees.index');
    }

    public function render()
    {
        $checklistItems = ChecklistItem::where('order', 1)->orderBy('name')->get();

        return view('livewire.employee.employee-edit', [
            'allEmployees' => Employee::select('id', 'name')->where('id', '!=', $this->form->employee->id)->get(),
            'checklistItems' => $checklistItems,
        ]);
    }
}
