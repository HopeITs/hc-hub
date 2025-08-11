<?php

namespace App\Livewire\Employee;

use App\Livewire\Forms\EmployeeForm;
use App\Models\Employee;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeCreate extends Component
{
    use WithFileUploads;

    public EmployeeForm $form;

    public function mount()
    {
        $this->form->addCourse();
        $this->form->addExperience();
        $this->form->addRelative();
        $this->form->addBadge();
        $this->form->addChecklist();
    }

    public function save()
    {
        $this->form->store();
        LivewireAlert::title(__('general.success-message'))->success()->show();
        return redirect()->route('employees.index');
    }

    public function render()
    {
        return view('livewire.employee.employee-create', [
            'allEmployees' => Employee::select('id', 'name')->get(),
        ]);
    }
}
