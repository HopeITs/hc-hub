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
        $project->load('programManager', 'centers.city', 'centers.projectManager');
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
                'contracts' => function ($q) use ($project) {
                    $q->whereHas('position.center', fn($qq) => $qq->where('project_id', $project->id))
                        ->with('position.center');
                },
            ])
            ->paginate(15);

        return view('livewire.project-management.project.detail', compact('project' ,'employees'));
    }
}
