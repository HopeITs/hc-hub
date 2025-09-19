<?php

namespace App\Livewire\ProgramManagement\Program;

use App\Livewire\Forms\ProgramManagement\ProgramForm;
use App\Models\Employee;
use App\Models\Program;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class Detail extends Component
{
    public $slug;
    public ProgramForm $form;

    public function mount(Program $program)
    {
        $program->load('centers.city');
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
                'contracts' => function ($q) use ($program) {
                    $q->whereHas('position.center', fn($qq) => $qq->where('program_id', $program->id))
                        ->with('position.center');
                },
            ])
            ->paginate(15);

        return view('livewire.program-management.program.detail', compact('program', 'employees'));
    }

    public function confirmDelete($id)
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.primary'))
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete(Program $program)
    {
        $program->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        return redirect()->route('programs.index');
    }
}
