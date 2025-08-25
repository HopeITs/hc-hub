<?php

namespace App\Livewire\ProjectManagement\Project;

use App\Livewire\Forms\ProjectManagement\ProjectForm;
use App\Models\Employee;
use App\Models\Project;
use Livewire\Component;

class Detail extends Component
{
    public $slug;
    public ProjectForm $form;


    public function mount(Project $project)
    {
        $this->form->setProject($project);
    }

    public function render()
    {
        $project = $this->form->project;
        $employees = Employee::query()
            ->select('employees.*')
            ->involvedInProject($project->id)
            ->distinct()
            ->with([
                // eager-load only the contracts tied to THIS project (and their position/center)
                'contracts' => function ($q) use ($project) {
                    $q->whereHas('position.center', fn($qq) => $qq->where('project_id', $project->id))
                        ->with('position.center');
                },
            ])
            ->paginate(15);

        return view('livewire.project-management.project.detail', compact('project' ,'employees'));
    }
}
