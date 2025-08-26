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
    use WithFileUploads , WithAlerts;

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
        // Seed base info first
        $this->infoForm->setEmployee($employee);

        // Seed each sub-form with the same employee (so they can load existing rows)
        $this->educationForm->setEmployee($employee);
//        $this->courseForm->setEmployee($employee ?? null);
//        $this->experienceForm->setEmployee($employee ?? null);
//        $this->relativeForm->setEmployee($employee ?? null);
//        $this->badgeForm->setEmployee($employee ?? null);
//        $this->checklistItemForm->setEmployee($employee ?? null);

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
        if (empty($this->checklistItemForm->checklists ?? [])) {
            $this->checklistItemForm->addChecklist();
        }
    }


    public function saveInformation()
    {
        $this->infoForm->saveInformation();
        $this->successToast();
        return $this->redirect(route('employees.index'), navigate: true);
    }

    public function addEducation(): void
    {
        $this->educationForm->addEducation();
    }

    public function removeEducation(int $index): void
    {
        $this->educationForm->removeEducation($index);
        // Optional: clear stale validation errors for removed index
        $this->resetErrorBag();
    }

    public function saveEducation(): void
    {
        $this->educationForm->save();
        $this->setActiveTab('education');
        $this->successToast(); // from WithAlerts
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
