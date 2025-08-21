<?php

namespace App\Livewire\Employee\Tabs;

use App\Livewire\Forms\Employee\PersonalInfoForm;
use App\Models\Employee;
use App\Traits\WithAlerts;
use Livewire\Component;

class PersonalInfoTab extends Component
{
    public PersonalInfoForm $form;
    use WithAlerts;
    public function save()
    {

        $employee = $this->form->saveInformation();

        $this->successToast();

        return $this->redirect(route('employees.edit', $employee->id), navigate: true);
    }
    public function render()
    {
        return view('livewire.employee.tabs.personal-info-tab',[
            'allEmployees' => Employee::select('id', 'name')->get(),
        ]);
    }
}
