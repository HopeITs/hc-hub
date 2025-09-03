<?php

namespace App\Livewire\Employee;

use App\Livewire\Forms\Employee\AttachmentForm;
use App\Livewire\Forms\Employee\BadgeForm;
use App\Livewire\Forms\Employee\ChecklistForm;
use App\Livewire\Forms\Employee\CourseForm;
use App\Livewire\Forms\Employee\EducationForm;
use App\Livewire\Forms\Employee\ExperienceForm;
use App\Livewire\Forms\Employee\ItemForm;
use App\Livewire\Forms\Employee\PersonalInfoForm;
use App\Livewire\Forms\Employee\RelativeForm;
use App\Models\ChecklistItem;
use App\Models\Employee;
use App\Traits\WithAlerts;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeCreate extends Component
{
    use WithFileUploads, WithAlerts;

    public PersonalInfoForm $infoForm;
    public EducationForm $educationForm;
    public ChecklistForm $checklistForm;
    public CourseForm $courseForm;
    public ExperienceForm $experienceForm;
    public RelativeForm $relativeForm;
    public BadgeForm $badgeForm;
    public AttachmentForm $attachmentForm;
    public ItemForm $itemForm;

    public $activeTab = 'information';

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function toggleBoardMember($checked): void
    {
        $this->infoForm->is_board_member = (bool) $checked;
        if (!$this->infoForm->is_board_member) {
            $this->infoForm->board_member_started_at = null;
            $this->infoForm->board_member_end_at = null;
        }
    }

    public function saveInformation()
    {
        $employee = $this->infoForm->saveInformation();
        $this->successToast();
        return $this->redirect(route('employees.edit', $employee->id), navigate: true);
    }

    public function saveEducation()
    {
        $this->educationForm->saveEducation();
        $this->successToast();
    }

    public function saveCourses()
    {
        $this->courseForm->saveCourses();
        $this->successToast();
    }

    public function saveExperience()
    {
        $this->experienceForm->saveExperience();
        $this->successToast();
    }

    public function saveRelatives()
    {
        $this->relativeForm->saveRelatives();
        $this->successToast();
    }

    public function saveBadges()
    {
        $this->badgeForm->save();
        $this->successToast();
    }

    public function saveChecklist()
    {
        $this->checklistForm->save();
        LivewireAlert::success(__('general.success-message'));
    }

    public function addAttachment()
    {
        $this->attachmentForm->addAttachment();
    }

    public function removeAttachment($index, $id = null)
    {
        $this->attachmentForm->removeAttachment((int) $index, $id);
    }

    public function addItem()
    {
        $this->itemForm->addItem();
    }

    public function removeItem($index, $id = null)
    {
        $this->itemForm->removeItem((int) $index, $id);
    }

    public function saveItems()
    {
        $this->itemForm->save();
        $this->successToast();
    }

    public function addEducation()
    {
        $this->educationForm->addEducation();
    }

    public function removeEducation($i)
    {
        $this->educationForm->removeEducation((int) $i);
    }

    public function addCourse()
    {
        $this->courseForm->addCourse();
    }

    public function removeCourse($i)
    {
        $this->courseForm->removeCourse((int) $i);
    }

    public function addExperience()
    {
        $this->experienceForm->addExperience();
    }

    public function removeExperience($i)
    {
        $this->experienceForm->removeExperience((int) $i);
    }

    public function addRelative()
    {
        $this->relativeForm->addRelative();
    }

    public function removeRelative($i)
    {
        $this->relativeForm->removeRelative((int) $i);
    }

    public function addBadge()
    {
        $this->badgeForm->addBadge();
    }

    public function removeBadge($i)
    {
        $this->badgeForm->removeBadge((int) $i);
    }

    public function addChecklist()
    {
        $this->checklistForm->addChecklist();
    }

    public function removeChecklist($i)
    {
        $this->checklistForm->removeChecklist((int) $i);
    }

    public function render()
    {
        return view('livewire.employee.form', [
            'allEmployees' => Employee::select('id', 'name')->get(),
            'checklistItems' => ChecklistItem::where('order', 1)->orderBy('name')->get(),
        ]);
    }
}
