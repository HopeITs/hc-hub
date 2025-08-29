<?php

namespace App\Livewire\Forms\ProgramManagement;

use App\Models\Program;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProgramForm extends Form
{

    public ?Program $program = null;

    public $name;
    public $description = [];
    public $start_date;
    public $end_date;
    public $code;
    public $slug;
    public $logo = null;
    public $is_active = true;

    protected function rules()
    {
        return [
            'name' => ['required', 'string'],
            'description.en' => ['nullable', 'min:3', 'max:255'],
            'description.ar' => ['nullable', 'min:3', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'slug' => [
                'required',
                Rule::unique('programs')->ignore($this->program),
            ],
            'code' => 'required',
            'logo' => 'nullable|image',

        ];
    }

    public function setProgram(Program $program)
    {
        $this->program = $program;
        $this->name = $program->name;
        $this->description = $program->getTranslations('description') ;
        $this->slug = $program->slug;
        $this->start_date = $program->start_date;
        $this->end_date = $program->end_date;
        $this->code = $program->code;
        $this->logo = $program->getFirstMediaUrl('logo');
        $this->is_active = $program->is_active;

    }

    public function store()
    {
        $this->validate();

        $program = Program::create($this->except('logo'));

        if ($this->logo != null) {
            $program->addMedia($this->logo->getRealPath())
                ->usingFileName('program-logo.' . $this->logo->getClientOriginalExtension())
                ->toMediaCollection('logo');
        }

        $this->reset();


    }

    public function update()
    {
        $this->validate();

        $this->program->update(
            $this->all()
        );
        if ($this->logo) {
            $this->program->clearMediaCollection('logo');
            $this->program->addMedia($this->logo->getRealPath())
                ->usingFileName('program-logo.' . $this->logo->getClientOriginalExtension())
                ->toMediaCollection('logo');
        }
        $this->reset();
    }

    public function statusToggle(){
        $this->program->update(
           [
               'is_active' => !$this->program->is_active,
           ]
        );
    }

}
