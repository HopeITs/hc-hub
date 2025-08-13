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
        if (empty($this->form->educations)) $this->form->addEducation();
        if (empty($this->form->courses)) $this->form->addCourse();
        if (empty($this->form->experiences)) $this->form->addExperience();
        if (empty($this->form->relatives)) $this->form->addRelative();
        if (empty($this->form->badges)) $this->form->addBadge();
        if (empty($this->form->checklists)) $this->form->addChecklist();
    }

    public function saveInformation() { $this->form->saveInformation(true); LivewireAlert::success(__('general.success-message')); }
    public function saveEducation() { $this->form->saveEducation(true); LivewireAlert::success(__('general.success-message')); }
    public function saveCourses() { $this->form->saveCourses(true); LivewireAlert::success(__('general.success-message')); }
    public function saveExperience() { $this->form->saveExperience(true); LivewireAlert::success(__('general.success-message')); }
    public function saveRelatives() { $this->form->saveRelatives(true); LivewireAlert::success(__('general.success-message')); }
    public function saveBadges() { $this->form->saveBadges(true); LivewireAlert::success(__('general.success-message')); }
    public function saveChecklist() { $this->form->saveChecklist(true); LivewireAlert::success(__('general.success-message')); }

    public function render()
    {
        $checklistItems = ChecklistItem::where('order', 1)->orderBy('name')->get();

        return view('livewire.employee.employee-edit', [
            'allEmployees' => Employee::select('id', 'name')->where('id', '!=', $this->form->employee->id)->get(),
            'checklistItems' => $checklistItems,
        ]);
    }
}
