<?php

namespace App\Livewire\Forms\ProjectManagement;

use App\Models\Project;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProjectForm extends Form
{

    public ?Project $project = null;

    public $name = [];
    public $description = [];
    public $start_date;
    public $end_date;
    public $code;
    public $slug;
    public $logo = null;

    protected function rules()
    {
        return [
            'name.ar' => ['required', 'min:3', 'max:255'],
            'name.en' => ['required', 'min:3', 'max:255'],
            'description.en' => ['nullable', 'min:3', 'max:255'],
            'description.ar' => ['nullable', 'min:3', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'slug' => [
                'required',
                Rule::unique('projects')->ignore($this->project),
            ],
            'code' => 'required',
            'logo' => 'nullable|image',
        ];
    }

    public function setProject(Project $project)
    {
        $this->project = $project;
        $this->name = $project->getTranslations('name');
        $this->description = $project->getTranslations('description') ;
        $this->slug = $project->slug;
        $this->start_date = $project->start_date;
        $this->end_date = $project->end_date;
        $this->code = $project->code;
        $this->logo = $project->getFirstMediaUrl('logo');

    }

    public function store()
    {
        $this->validate();

        $project = Project::create($this->except('logo'));

        if ($this->logo != null) {
            $project->addMedia($this->logo->getRealPath())
                ->usingFileName('project-logo.' . $this->logo->getClientOriginalExtension())
                ->toMediaCollection('logo');
        }

        $this->reset();


    }

    public function update()
    {
        $this->validate();

        $this->project->update(
            $this->all()
        );
        if ($this->logo) {
            $this->project->clearMediaCollection('logo');
            $this->project->addMedia($this->logo->getRealPath())
                ->usingFileName('project-logo.' . $this->logo->getClientOriginalExtension())
                ->toMediaCollection('logo');
        }
        $this->reset();
    }

}
