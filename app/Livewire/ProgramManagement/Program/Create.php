<?php

namespace App\Livewire\ProgramManagement\Program;

use App\Livewire\Forms\ProgramManagement\ProgramForm;
use App\Models\Employee;
use App\Models\Program;
use App\Traits\WithAlerts;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithAlerts, WithFileUploads;

    public ProgramForm $form;
    public $existingLogoUrl = '';

    public function save()
    {
        $this->form->store();
        $this->successToast(__('general.success-message'));
        return redirect()->route('programs.index');
    }

    public function render()
    {
        $locale = app()->getLocale();
        $employees = Employee::orderBy(transColumn('name'))->get(['id', transColumn('name')]);

        return view('livewire.program-management.program.form', [
            'title' => __('general.add-new'),
            'employees' => $employees,
        ]);
    }
}
