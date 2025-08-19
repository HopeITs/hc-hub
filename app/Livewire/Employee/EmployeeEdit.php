<?php

namespace App\Livewire\Employee;

use App\Livewire\Forms\Employee\PersonalInfoForm;
use App\Models\ChecklistItem;
use App\Models\Employee;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeEdit extends Component
{
    use WithFileUploads;

    public PersonalInfoForm $form;

    public function mount(Employee $employee)
    {
        $this->form->setEmployee($employee);

        if (empty($this->form->educations)) {
            $this->form->addEducation();
        }
        if (empty($this->form->courses)) {
            $this->form->addCourse();
        }
        if (empty($this->form->experiences)) {
            $this->form->addExperience();
        }
        if (empty($this->form->relatives)) {
            $this->form->addRelative();
        }
        if (empty($this->form->badges)) {
            $this->form->addBadge();
        }
        if (empty($this->form->checklists)) {
            $this->form->addChecklist();
        }
    }

    public function saveInformation()
    {
        $this->form->saveInformation();
        LivewireAlert::success(__('general.success-message'));
        return $this->redirect(route('employees.index'), navigate: true);
    }

    public function saveEducation()
    {
        $this->form->saveEducation();
        LivewireAlert::success(__('general.success-message'));
    }

    public function saveCourses()
    {
        $this->form->saveCourses();
        LivewireAlert::success(__('general.success-message'));
    }

    public function saveExperience()
    {
        $this->form->saveExperience();
        LivewireAlert::success(__('general.success-message'));
    }

    public function saveRelatives()
    {
        $this->form->saveRelatives();
        LivewireAlert::success(__('general.success-message'));
    }

    public function saveBadges()
    {
        $this->form->saveBadges();
        LivewireAlert::success(__('general.success-message'));
    }

    public function saveChecklist()
    {
        $this->form->saveChecklist();
        LivewireAlert::success(__('general.success-message'));
    }

    public function render()
    {
        $checklistItems = ChecklistItem::where('order', 1)->orderBy('name')->get();

        return view('livewire.employee.employee-edit', [
            'allEmployees' => Employee::select('id', 'name')
                ->where('id', '!=', $this->form->employee->id)
                ->get(),
            'checklistItems' => $checklistItems,
        ]);
    }
}
