<?php

namespace App\Livewire\ProjectManagement;

use App\Models\Project;
use Livewire\Component;

class ProjectDetail extends Component
{
    public Project $preject;
    public function render($slug)
    {
        $project = Project();
        return view('livewire.project-management.project-detail' , compact('project'));
    }
}
