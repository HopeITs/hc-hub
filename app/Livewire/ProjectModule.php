<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;

class ProjectModule extends Component
{
    public function render()
    {
        $items = Project::all();
        return view('livewire.project-module' ,compact('items'));
    }
}
