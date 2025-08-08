<?php

namespace App\Livewire\ProjectManagement;

use App\Models\Project;
use Livewire\Component;

class ProjectDetail extends Component
{
    public $slug ;

    public function render()
    {
        $project = Project::whereSlug($this->slug)->firstOrFail();

        return view('livewire.project-management.project-detail' , compact('project'));
    }
}
