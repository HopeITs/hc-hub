<?php

namespace App\Livewire\ProjectManagement\Project;

use App\Livewire\Forms\ProjectManagement\ProjectForm;
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
        return view('livewire.project-management.project.detail', compact('project'));
    }
}
