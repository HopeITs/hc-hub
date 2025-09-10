<?php

namespace App\Livewire\ProgramManagement\Program;

use App\Livewire\Forms\ProgramManagement\ProgramForm;
use App\Models\Employee;
use Livewire\Component;

class Detail extends Component
{
    public $slug;
    public ProgramForm $form;


    public function mount(Program $program)
    {
        $program->load('programManager', 'centers.city', 'centers.projectManager');
        $this->form->setProgram($program);
    }

    public function render()
    {
        $program = $this->form->program;
        $employees = Employee::query()
            ->select('employees.*')
            ->involvedInProgram($program->id)
            ->distinct()
            ->with([
                // eager-load only the contracts tied to THIS project (and their position/center)
                'contracts' => function ($q) use ($program) {
                    $q->whereHas('position.center', fn($qq) => $qq->where('program_id', $program->id))
                        ->with('position.center');
                },
            ])
            ->paginate(15);

        return view('livewire.program-management.program.detail', compact('program' ,'employees'));
    }
}
