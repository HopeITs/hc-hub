<?php

namespace App\Livewire\Employee;

use App\Livewire\Forms\Employee\BadgeForm;
use App\Livewire\Forms\Employee\ChecklistForm;
use App\Livewire\Forms\Employee\CourseForm;
use App\Livewire\Forms\Employee\EducationForm;
use App\Livewire\Forms\Employee\ExperienceForm;
use App\Livewire\Forms\Employee\PersonalInfoForm;
use App\Livewire\Forms\Employee\RelativeForm;
use App\Models\ChecklistItem;
use App\Models\Employee;
use App\Traits\WithAlerts;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeEdit extends Component
{
    use WithFileUploads, WithAlerts;

    public PersonalInfoForm $infoForm;
    public EducationForm $educationForm;
    public ChecklistForm $checklistForm;
    public CourseForm $courseForm;
    public ExperienceForm $experienceForm;
    public RelativeForm $relativeForm;
    public BadgeForm $badgeForm;

    public $activeTab = 'information';

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function mount(Employee $employee)
    {
        $this->infoForm->setEmployee($employee);
        $this->educationForm->setEmployee($employee);
        $this->courseForm->setEmployee($employee);
        $this->experienceForm->setEmployee($employee);
        $this->relativeForm->setEmployee($employee);
        $this->badgeForm->setEmployee($employee);
        $this->checklistForm->setEmployee($employee);

        if (empty($this->educationForm->educations)) {
            $this->educationForm->addEducation();
        }
        if (empty($this->courseForm->courses ?? [])) {
            $this->courseForm->addCourse();
        }
        if (empty($this->experienceForm->experiences ?? [])) {
            $this->experienceForm->addExperience();
        }
        if (empty($this->relativeForm->relatives ?? [])) {
            $this->relativeForm->addRelative();
        }
        if (empty($this->badgeForm->badges ?? [])) {
            $this->badgeForm->addBadge();
        }
        if (empty($this->checklistForm->checklists ?? [])) {
            $this->checklistForm->addChecklist();
        }
    }

    public function saveInformation()
    {
        $this->infoForm->saveInformation();
        $this->successToast();
        return $this->redirect(route('employees.index'), navigate: true);
    }

    public function addEducation()
    {
        $this->educationForm->addEducation();
    }
    public function removeEducation(int $index, int $id = null)
    {
        $this->educationForm->removeEducation($index, $id);
        $this->resetErrorBag();
    }
    public function saveEducation()
    {
        $this->educationForm->save();
        $this->setActiveTab('education');
        $this->successToast();
    }

    public function addCourse()
    {
        $this->courseForm->addCourse();
    }
    public function removeCourse(int $index)
    {
        $this->courseForm->removeCourse($index);
        $this->resetErrorBag();
    }
    public function saveCourses()
    {
        $this->courseForm->save();
        $this->setActiveTab('courses');
        $this->successToast();
    }

    public function saveExperience()
    {
        $this->experienceForm->saveExperience();
        LivewireAlert::success(__('general.success-message'));
    }

    public function saveRelatives()
    {
        $this->relativeForm->saveRelatives();
        LivewireAlert::success(__('general.success-message'));
    }

    public function saveBadges()
    {
        $this->badgeForm->save();
        LivewireAlert::success(__('general.success-message'));
    }

    public function addChecklist()
    {
        $this->checklistForm->addChecklist();
    }
    public function removeChecklist($i, $id = null)
    {
        $this->checklistForm->removeChecklist((int) $i, $id);
    }
    public function saveChecklist()
    {
        $this->checklistForm->save();
        LivewireAlert::success(__('general.success-message'));
    }

    public function render()
    {
        $checklistItems = ChecklistItem::where('order', 1)->orderBy('name')->get();

        return view('livewire.employee.form', [
            'allEmployees' => Employee::select('id', 'name')
                ->where('id', '!=', $this->infoForm->employee->id)
                ->get(),
            'checklistItems' => $checklistItems,
        ]);
    }
}
