<?php

namespace App\Livewire\Employee;

use App\Livewire\Forms\Employee\PersonalInfoForm;
use App\Models\ChecklistItem;
use App\Models\Employee;
use App\Traits\WithAlerts;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeCreate extends Component
{
    use WithFileUploads , WithAlerts;

    public PersonalInfoForm $form;



    public function saveInformation()
    {
        $employee = $this->form->saveInformation();

        $this->successToast();

        return $this->redirect(route('employees.edit', $employee->id), navigate: true);
    }
    public function saveEducation()
    {
        $this->form->saveEducation();
        $this->successToast();
    }
    public function saveCourses()
    {
        $this->form->saveCourses();
        $this->successToast();
    }
    public function saveExperience()
    {
        $this->form->saveExperience();
        $this->successToast();
    }
    public function saveRelatives()
    {
        $this->form->saveRelatives();
        $this->successToast();
    }
    public function saveBadges()
    {
        $this->form->saveBadges();
        $this->successToast();

    }
    public function saveChecklist()
    {
        $this->form->saveChecklist();
        LivewireAlert::success(__('general.success-message'));
    }

    public function addEducation()
    {
        $this->form->addEducation();
    }
    public function removeEducation($i)
    {
        $this->form->removeEducation((int) $i);
    }

    public function addCourse()
    {
        $this->form->addCourse();
    }
    public function removeCourse($i)
    {
        $this->form->removeCourse((int) $i);
    }

    public function addExperience()
    {
        $this->form->addExperience();
    }
    public function removeExperience($i)
    {
        $this->form->removeExperience((int) $i);
    }

    public function addRelative()
    {
        $this->form->addRelative();
    }
    public function removeRelative($i)
    {
        $this->form->removeRelative((int) $i);
    }

    public function addBadge()
    {
        $this->form->addBadge();
    }
    public function removeBadge($i)
    {
        $this->form->removeBadge((int) $i);
    }

    public function addChecklist()
    {
        $this->form->addChecklist();
    }
    public function removeChecklist($i)
    {
        $this->form->removeChecklist((int) $i);
    }

    public function render()
    {
        $checklistItems = ChecklistItem::where('order', 1)->orderBy('name')->get();

        return view('livewire.employee.employee-create', [
            'allEmployees' => Employee::select('id', 'name')->get(),
            'checklistItems' => $checklistItems,
        ]);
    }
}
