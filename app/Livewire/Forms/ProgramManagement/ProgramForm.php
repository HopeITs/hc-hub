<?php

namespace App\Livewire\Forms\ProgramManagement;

use App\Models\Program;
use App\Rules\SafeImage;
use App\Services\SecureImageUploadService;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class ProgramForm extends Form
{
    public ?Program $program = null;

    public $name;

    public $description_en = '';

    public $description_ar = '';

    public $start_date;

    public $end_date;

    public $code;

    public $slug;

    public $logo = null;

    public $is_active = true;

    public function setProgram(Program $program)
    {
        $this->program = $program;
        $this->name = $program->name;
        $this->description_en = $program->description_en;
        $this->description_ar = $program->description_ar;
        $this->slug = $program->slug;
        $this->start_date = $program->start_date?->format('Y-m-d');
        $this->end_date = $program->end_date?->format('Y-m-d');
        $this->code = $program->code;
        $this->logo = $program->getFirstMediaUrl('logo');
        $this->is_active = $program->is_active;

    }

    public function store()
    {
        $this->validate();

        $program = Program::create($this->except('logo'));

        $this->storeLogo($program);

        $this->reset();

    }

    public function update()
    {
        $this->validate();

        $this->program->update(
            $this->except('logo'),
        );

        $this->storeLogo($this->program);

        $this->reset();
    }

    public function statusToggle()
    {
        $this->program->update(
            [
                'is_active' => ! $this->program->is_active,
            ],
        );
    }

    protected function rules()
    {
        return [
            'name' => ['required', 'string'],
            'description_en' => ['nullable', 'min:3', 'max:255'],
            'description_ar' => ['nullable', 'min:3', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'slug' => [
                'required',
                Rule::unique('programs')->ignore($this->program),
            ],
            'code' => 'required',
            // The mimes list is a first pass only; SafeImage inspects the file
            // content and SecureImageUploadService re-encodes it at store time.
            'logo' => ['nullable', 'mimes:jpeg,jpg,png,webp', new SafeImage('logo')],

        ];
    }

    /**
     * Replace the program logo with a server re-encoded copy.
     *
     * The upload never keeps its original name or extension, so a polyglot
     * image/PHP file cannot be written into publicly served storage.
     */
    private function storeLogo(Program $program): void
    {
        if (! $this->logo instanceof TemporaryUploadedFile) {
            return;
        }

        $program->clearMediaCollection('logo');

        app(SecureImageUploadService::class)->attachTo(
            model: $program,
            file: $this->logo,
            collection: 'logo',
            profile: 'logo',
            disk: 'public',
        );
    }
}
