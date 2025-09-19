<?php

namespace App\Livewire\Forms\ProgramManagement;

use App\Models\Program;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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
    public $program_manager_id = null;

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
            'logo' => 'nullable',
            'program_manager_id' => ['nullable', 'exists:employees,id'],
        ];
    }

    public function setProgram(Program $program)
    {
        $this->program = $program;
        $this->name = $program->name;
        $this->description = $program->getTranslations('description') ;
        $this->slug = $program->slug;
        $this->start_date = $program->start_date?->format('Y-m-d');
        $this->end_date = $program->end_date?->format('Y-m-d');
        $this->code = $program->code;
        $this->logo = $program->getFirstMediaUrl('logo');
        $this->is_active = $program->is_active;
        $this->program_manager_id = $program->contracts->first()?->employee_id;
    }

    public function store()
    {
        $this->validate();

        $program = Program::create($this->except('logo', 'program_manager_id'));

        if ($this->logo instanceof TemporaryUploadedFile) {
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
            $this->except('logo', 'program_manager_id')
        );

        if ($this->logo instanceof TemporaryUploadedFile) {
            $this->program->clearMediaCollection('logo');
            $this->program->addMedia($this->logo->getRealPath())
                ->usingFileName('program-logo.' . $this->logo->getClientOriginalExtension())
                ->toMediaCollection('logo');
        }
    }

    public function statusToggle()
    {
        $this->program->update(
            [
                'is_active' => !$this->program->is_active,
            ]
        );
    }
}
