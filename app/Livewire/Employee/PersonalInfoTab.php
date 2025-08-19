<?php

namespace App\Livewire\Employee;

use App\Livewire\Forms\Employee\PersonalInfoForm;
use App\Models\Employee;
use Livewire\Component;

class PersonalInfoTab extends Component
{
    public PersonalInfoForm $form;
    public function saveInformation()
    {

        $employee = $this->form->saveInformation();

        $this->successToast();

        return $this->redirect(route('employees.edit', $employee->id), navigate: true);
    }
    public function render()
    {
        return view('livewire.employee.personal-info-tab',[
            'allEmployees' => Employee::select('id', 'name')->get(),
        ]);
    }
}
