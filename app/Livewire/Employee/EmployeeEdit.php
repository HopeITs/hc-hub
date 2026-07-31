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
use App\Livewire\Forms\LeaveBalanceForm;
use App\Models\Badge;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\City;
use App\Models\DefaultDeliverable;
use App\Models\Employee;
use App\Services\ChecklistWorkflow;
use App\Traits\WithAlerts;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeEdit extends Component
{
    use WithAlerts, WithFileUploads;

    public ?Employee $employee = null;

    #[Locked]
    public bool $canManageBadges = false;

    public PersonalInfoForm $infoForm;

    public EducationForm $educationForm;

    public ChecklistForm $checklistForm;

    public CourseForm $courseForm;

    public ExperienceForm $experienceForm;

    public RelativeForm $relativeForm;

    public BadgeForm $badgeForm;

    public AttachmentForm $attachmentForm;

    public LeaveBalanceForm $balanceForm;

    public $activeTab = 'information';

    public $title = '';

    public $showInitiateReturnModal = false;

    public $selectedChecklistForReturn;

    public $return_receiver_id;

    public function mount(Employee $employee)
    {
        Gate::authorize('update', $employee);

        $this->employee = $employee;
        $this->title = $employee->name;
        $this->canManageBadges = Gate::allows('create', [Badge::class, $employee]);

        $this->infoForm->setEmployee($employee);
        $this->educationForm->setEmployee($employee);
        $this->courseForm->setEmployee($employee);
        $this->experienceForm->setEmployee($employee);
        $this->relativeForm->setEmployee($employee);
        if ($this->canManageBadges) {
            $this->badgeForm->setEmployee($employee);
        }
        $this->attachmentForm->setEmployee($employee);

        $hasExistingChecklist = Checklist::where('employee_id', $employee->id)->exists();
        $defaultDeliverables = $hasExistingChecklist ? collect() : DefaultDeliverable::all();

        $this->checklistForm->setEmployeeAndAllItems($employee, $this->getChecklistItems(), $defaultDeliverables);
    }

    public function saveChecklist()
    {
        $this->checklistForm->save();
        $this->successToast();

        $this->checklistForm->setEmployeeAndAllItems(
            $this->employee->fresh(),
            $this->getChecklistItems(),
            DefaultDeliverable::all()
        );
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function saveInformation()
    {

        $this->infoForm->saveInformation();
        $employee = $this->infoForm->employee;
        if ($employee->wasChanged('gender') || $employee->wasChanged('marital_status')) {
            if ($employee->contracts()->exists()) {
                $contract = $employee->contracts()->whereHas('leaveBalances')->first();
                if (isset($contract)) {
                    if ($contract->leaveBalances()->exists()) {
                        $this->balanceForm->update($contract->id);
                    } else {
                        $this->balanceForm->store($contract->id);
                    }
                }
            }
        }
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
        Gate::authorize('create', [Badge::class, $this->employee]);
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
        Gate::authorize('create', [Badge::class, $this->employee]);
        $this->badgeForm->addBadge();
    }

    public function removeBadge(int $index)
    {
        Gate::authorize('create', [Badge::class, $this->employee]);
        $this->badgeForm->removeBadge($index);
        $this->resetErrorBag();
    }

    public function openInitiateReturnModal(int $checklistId)
    {
        Gate::authorize('update', $this->employee);

        // Scoped to the employee being edited. Previously any checklist id at
        // all could be loaded into the modal — including one belonging to
        // another city's employee — and then written to by initiateReturn().
        $this->selectedChecklistForReturn = Checklist::query()
            ->whereKey($checklistId)
            ->where('employee_id', $this->employee->id)
            ->firstOrFail();

        $this->return_receiver_id = null;
        $this->showInitiateReturnModal = true;
    }

    public function initiateReturn()
    {
        Gate::authorize('update', $this->employee);

        $this->validate(['return_receiver_id' => 'required|exists:employees,id']);

        if ($this->selectedChecklistForReturn) {
            app(ChecklistWorkflow::class)->initiateReturn(
                checklistId: (int) $this->selectedChecklistForReturn->id,
                employee: $this->employee,
                receiverId: (int) $this->return_receiver_id,
            );

            $this->successToast('Return process initiated.');
            $this->showInitiateReturnModal = false;
            $this->selectedChecklistForReturn = null;
        }

        $this->checklistForm->setEmployeeAndAllItems(
            $this->employee->fresh(),
            $this->getChecklistItems(),
            DefaultDeliverable::all()
        );
    }

    public function finalizeReturnCycle(int $checklistId)
    {
        Gate::authorize('update', $this->employee);

        app(ChecklistWorkflow::class)->finalizeReturn($checklistId, $this->employee);

        $this->successToast('Checklist fully reset.');

        $this->checklistForm->setEmployeeAndAllItems(
            $this->employee->fresh(),
            $this->getChecklistItems(),
            DefaultDeliverable::all()
        );
    }

    public function toggleBoardMember($checked): void
    {
        $this->infoForm->is_board_member = (bool) $checked;
    }

    public function render()
    {
        $delivereds = Employee::whereIn('id', DefaultDeliverable::pluck('employee_id'))->get();
        if ($delivereds->isEmpty()) {
            $delivereds = Employee::whereHas(
                'contracts', function ($q) {
                    $q->whereHas('position.department', function ($q) {
                        $q->whereIn('name', ['IT', 'Communication', 'HR']);
                    });
                }
            )->get();
        }
        $cities = City::where('is_active', true)->pluck(transColumn('name'), 'id')->toArray();

        $employeeChecklistItems = Checklist::with(['item'])
            ->where('employee_id', $this->employee->id)
            ->where(function ($q) {
                $q->whereNull('delivered_status')
                    ->orWhereIn('delivered_status', ['delivered', 'pending'])
                    ->orWhere('returned_status', 'received');
            })
            ->orderByDesc('updated_at')
            ->get();

        return view('livewire.employee.form', [
            'cities' => $cities,
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
