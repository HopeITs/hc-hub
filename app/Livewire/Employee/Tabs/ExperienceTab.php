<?php

namespace App\Livewire\Employee\Tabs;

use App\Livewire\Forms\Employee\CourseForm;
use App\Livewire\Forms\Employee\PersonalInfoForm;
use Livewire\Component;

class ExperienceTab extends Component
{
    public CourseForm $form;
    public function saver()
    {

        $employee = $this->form->saveInformation();

        $this->successToast();

        return $this->redirect(route('employees.edit', $employee->id), navigate: true);
    }
    public function render()
    {
        return view('livewire.employee.tabs.courses-fields');
    }
}
