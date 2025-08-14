<?php

namespace App\Livewire\Employee;

use App\Livewire\Forms\EmployeeForm;
use App\Models\ChecklistItem;
use App\Models\Employee;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeCreate extends Component
{
    use WithFileUploads;

    public EmployeeForm $form;

    public function mount()
    {
        $this->form->addEducation();
        $this->form->addCourse();
        $this->form->addExperience();
        $this->form->addRelative();
        $this->form->addBadge();
        $this->form->addChecklist();
    }

    // ==== Proxy actions for the form (Livewire can call these) ====
    public function addEducation()        { $this->form->addEducation(); }
    public function removeEducation($i)   { $this->form->removeEducation((int) $i); }

    public function addCourse()           { $this->form->addCourse(); }
    public function removeCourse($i)      { $this->form->removeCourse((int) $i); }

    public function addExperience()       { $this->form->addExperience(); }
    public function removeExperience($i)  { $this->form->removeExperience((int) $i); }

    public function addRelative()         { $this->form->addRelative(); }
    public function removeRelative($i)    { $this->form->removeRelative((int) $i); }

    public function addBadge()            { $this->form->addBadge(); }
    public function removeBadge($i)       { $this->form->removeBadge((int) $i); }

    public function addChecklist()        { $this->form->addChecklist(); }
    public function removeChecklist($i)   { $this->form->removeChecklist((int) $i); }
    // =============================================================

    public function save()
    {
        dd($this->form);
        $this->form->store();
        LivewireAlert::title(__('general.success-message'))->success()->show();
        return redirect()->route('employees.index');
    }

    public function render()
    {
        $checklistItems = ChecklistItem::where('order', 1)->orderBy('name')->get();

        return view('livewire.employee.employee-create', [
            'allEmployees'  => Employee::select('id', 'name')->get(),
            'checklistItems'=> $checklistItems,
        ]);
    }
}
