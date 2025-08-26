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
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeEdit extends Component
{
    use WithFileUploads;

    public PersonalInfoForm $infoForm;
    public EducationForm $educationForm;
    public ChecklistForm $checklistItemForm;
    public  CourseForm $courseForm;
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
dd($this->infoForm->employee);
        if (empty($this->infoForm->employee->educations)) {
            $this->educationForm->addEducation();
        }
        if (empty($this->infoForm->employee->courses)) {
            $this->courseForm->addCourse();
        }
        if (empty($this->infoForm->employee->experiences)) {
            $this->experienceForm->addExperience();
        }
        if (empty($this->infoForm->employee->relatives)) {
            $this->relativeForm->addRelative();
        }
        if (empty($this->infoForm->employee->badges)) {
            $this->badgeForm->addBadge();
        }
        if (empty($this->infoForm->employee->checklists)) {
            $this->checklistItemForm->addChecklist();
        }
    }

    public function saveInformation()
    {
        $this->infoForm->saveInformation();
        LivewireAlert::success(__('general.success-message'));
        return $this->redirect(route('employees.index'), navigate: true);
    }

    public function saveEducation()
    {
        $this->educationForm->saveEducation();
        LivewireAlert::success(__('general.success-message'));
    }

    public function saveCourses()
    {
        $this->courseForm->saveCourses();
        LivewireAlert::success(__('general.success-message'));
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
        $this->badgeForm->saveBadges();
        LivewireAlert::success(__('general.success-message'));
    }

    public function saveChecklist()
    {
        $this->checklistItemForm->saveChecklist();
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
