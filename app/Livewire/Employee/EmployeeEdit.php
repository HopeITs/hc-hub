<?php

namespace App\Livewire\Employee;

use App\Livewire\Forms\Employee\AttachmentForm;
use App\Livewire\Forms\Employee\BadgeForm;
use App\Livewire\Forms\Employee\ChecklistForm;
use App\Livewire\Forms\Employee\CourseForm;
use App\Livewire\Forms\Employee\EducationForm;
use App\Livewire\Forms\Employee\ExperienceForm;
use App\Livewire\Forms\Employee\PersonalInfoForm;
use App\Livewire\Forms\Employee\RelativeForm;
use App\Models\ChecklistItem;
use App\Models\DefaultDeliverable;
use App\Models\Employee;
use App\Models\Role;
use App\Traits\WithAlerts;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeEdit extends Component
{
    use WithFileUploads, WithAlerts;

    public ?Employee $employee = null;
    public PersonalInfoForm $infoForm;
    public EducationForm $educationForm;
    public ChecklistForm $checklistForm;
    public CourseForm $courseForm;
    public ExperienceForm $experienceForm;
    public RelativeForm $relativeForm;
    public BadgeForm $badgeForm;
    public AttachmentForm $attachmentForm;

    public $activeTab = 'information';
    public $title = '';

    public function mount(Employee $employee)
    {
        $this->employee = $employee;
        $this->title = $employee->name;

        $this->infoForm->setEmployee($employee);
        $this->educationForm->setEmployee($employee);
        $this->courseForm->setEmployee($employee);
        $this->experienceForm->setEmployee($employee);
        $this->relativeForm->setEmployee($employee);
        $this->badgeForm->setEmployee($employee);
        $this->attachmentForm->setEmployee($employee);

        $this->checklistForm->setEmployeeAndAllItems($employee, $this->getChecklistItems());
    }

    public function saveChecklist()
    {
        $this->checklistForm->save();
        $this->successToast();
        $this->checklistForm->setEmployeeAndAllItems($this->employee->fresh(), $this->getChecklistItems());
    }

    #region Other Methods
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    public function saveInformation()
    {
        $this->infoForm->saveInformation();
        $this->successToast();
        return $this->redirect(route('employees.index'), navigate: true);
    }
    public function saveEducation()
    {
        $this->educationForm->save();
        $this->successToast();
    }
    public function saveCourses()
    {
        $this->courseForm->save();
        $this->successToast();
    }
    public function saveExperience()
    {
        $this->experienceForm->save();
        $this->successToast();
    }
    public function saveRelatives()
    {
        $this->relativeForm->save();
        $this->successToast();
    }
    public function saveBadges()
    {
        $this->badgeForm->save();
        $this->successToast();
    }
    public function saveAttachments()
    {
        $this->attachmentForm->save();
        $this->successToast();
    }
    public function addAttachment()
    {
        $this->attachmentForm->addAttachment();
    }
    public function removeAttachment($index, $id = null)
    {
        $this->attachmentForm->removeAttachment($index, $id);
        $this->resetErrorBag();
    }
    public function addEducation()
    {
        $this->educationForm->addEducation();
    }
    public function removeEducation(int $index, ?int $id = null)
    {
        $this->educationForm->removeEducation($index, $id);
        $this->resetErrorBag();
    }
    public function addCourse()
    {
        $this->courseForm->addCourse();
    }
    public function removeCourse(int $index, ?int $id = null)
    {
        $this->courseForm->remove($index, $id);
        $this->resetErrorBag();
    }
    public function addExperience()
    {
        $this->experienceForm->addExperience();
    }
    public function removeExperience(int $index, ?int $id = null)
    {
        $this->experienceForm->removeExperience($index, $id);
        $this->resetErrorBag();
    }
    public function addRelative()
    {
        $this->relativeForm->addRelative();
    }
    public function removeRelative(int $index, ?int $id = null)
    {
        $this->relativeForm->removeRelative($index, $id);
        $this->resetErrorBag();
    }
    public function addBadge()
    {
        $this->badgeForm->addBadge();
    }
    public function removeBadge(int $index, ?int $id = null)
    {
        $this->badgeForm->removeBadge($index, $id);
        $this->resetErrorBag();
    }
    #endregion

    public function render()
    {
        $roles = Role::pluck('name', 'id')->toArray();
        $delivereds = Employee::whereIn('id', DefaultDeliverable::whereIn('city_id', [1, $this->employee->city_id])->pluck('employee_id'))->get();
        $employeeChecklistItems = $this->employee->checklists()->with('item')->get();

        return view('livewire.employee.form', [
            'roles' => $roles,
            'allEmployees' => Employee::select('id', 'name')->where('id', '!=', $this->employee->id)->get(),
            'delivereds' => $delivereds,
            'employeeChecklistItems' => $employeeChecklistItems,
            'isCreateMode' => false,
        ]);
    }

    private function getChecklistItems(): Collection
    {
        $query = ChecklistItem::query();

        if ($this->employee->created_at->greaterThan(Carbon::now()->subMonth())) {
            return $query->where('order', 1)->get();
        }

        return $query->whereNot('order', 1)->orderBy('order')->get();
    }
}
