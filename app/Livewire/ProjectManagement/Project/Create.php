<?php

namespace App\Livewire\ProjectManagement\Project;

use App\Livewire\Forms\ProjectManagement\ProjectForm;
use App\Models\Employee;
use App\Models\Project;
use App\Traits\WithAlerts;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithAlerts, WithFileUploads;

    public ProjectForm $form;
    public $existingLogoUrl = '';

    public function save()
    {
        $this->form->store();
        $this->successToast(__('general.success-message'));
        return redirect()->route('projects.index');
    }

    public function render()
    {
        $locale = app()->getLocale();
        $employees = Employee::orderBy("name->$locale")->get(['id', 'name']);

        return view('livewire.project-management.project.form', [
            'title' => __('general.add_new'),
            'employees' => $employees,
        ]);
    }
}
