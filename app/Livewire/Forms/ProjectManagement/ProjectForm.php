<?php

namespace App\Livewire\Forms\ProjectManagement;

use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProjectForm extends Form
{

    public ?Project $project;
    #[Validate(['name.en' => 'required|min:3', 'name.ar' => 'required'])]
    public $name = '';

    #[Validate(['description.en' => 'nullable|min:3', 'description.ar' => 'nullable'])]
    public $description = '';

    #[Validate('required|date')]
    public $start_date;

    #[Validate('nullable|date|after:form.start_date')]
    public $end_date;
    #[Validate('required')]
    public $code;

    public $slug;

    public $logo;

}
