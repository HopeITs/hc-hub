<?php

namespace App\Livewire\Employee\Tabs;

use App\Livewire\Forms\Employee\PersonalInfoForm;
use Livewire\Component;

class CourseTab extends Component
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
        return view('livewire.employee.partials.info-form',);
    }
}
