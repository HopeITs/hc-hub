<?php

namespace App\Livewire\Employee;

use App\Livewire\Forms\Employee\PersonalInfoForm;
use App\Models\Employee;
use App\Models\Role;
use App\Traits\WithAlerts;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmployeeCreate extends Component
{
    use WithFileUploads, WithAlerts;

    public PersonalInfoForm $infoForm;

    public $activeTab = 'information';
    public $title = '';

    public function mount()
    {
        $this->title = __('general.add-new');
    }

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

    public function render()
    {
        $roles = Role::pluck('name', 'id')->toArray();

        return view('livewire.employee.form', [
            'roles' => $roles,
            'allEmployees' => Employee::select('id', 'name')->get(),
            'isCreateMode' => true,
        ]);
    }
}
