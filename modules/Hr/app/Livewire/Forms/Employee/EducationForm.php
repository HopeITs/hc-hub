<?php

namespace Modules\Hr\Livewire\Forms\Employee;

use Modules\Hr\Enums\EducationStatus;
use Modules\Hr\Enums\EducationType;
use Modules\Hr\Models\Education;
use Modules\Hr\Models\Employee;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class EducationForm extends Form
{
    public ?Employee $employee = null;

    public ?Education $education = null;

    public array $educations = [];

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;

        $this->educations = $this->employee->educations()
            ->select('id', 'name', 'specialization', 'degree', 'status', 'notes')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'specialization' => $e->specialization,
                'degree' => $e->degree,
                'status' => $e->status,
                'edu_attachment' => $e->getFirstMedia('file')
                    ? route('employee-files.download', $e->getFirstMedia('file'))
                    : null,
                'file_name' => $e->getFirstMedia('file')->file_name ?? null,
                'notes' => $e->notes,
            ])->toArray();
    }

    public function rules(): array
    {
        $rules = [
            'educations' => ['array'],
            'educations.*.id' => ['nullable', 'exists:educations,id'],
            'educations.*.name' => ['required', 'string', 'max:255'],
            'educations.*.specialization' => ['nullable', 'string', 'max:255'],
            'educations.*.degree' => ['required', Rule::in(EducationType::getValues())],
            'educations.*.status' => ['required', Rule::in(EducationStatus::getValues())],
            'educations.*.edu_attachment' => ['nullable'],
            'educations.*.notes' => ['nullable', 'string', 'max:1000'],
        ];

        foreach ($this->educations as $index => $education) {
            if (($education['edu_attachment'] ?? null) instanceof TemporaryUploadedFile) {
                $rules["educations.{$index}.edu_attachment"] = ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];
            }
        }

        return $rules;
    }

    public function addEducation(): void
    {
        $this->educations[] = [
            'id' => null,
            'name' => '',
            'specialization' => '',
            'degree' => null,
            'status' => EducationStatus::Graduated,
            'edu_attachment' => null,
            'file_name' => null,
            'notes' => '',
        ];
    }

    public function removeEducation(int $index, $id = null): void
    {
        if (! array_key_exists($index, $this->educations)) {
            return;
        }

        unset($this->educations[$index]);

        if (isset($id)) {
            $this->employee->educations()->find($id)?->delete();
        }

        $this->educations = array_values($this->educations);
    }

    public function save(): void
    {
        $this->validate();

        foreach ($this->educations as $index => $education) {
            if (isset($education['id'])) {
                $this->education = $this->employee->educations()->findOrFail($education['id']);
                $this->education->update(collect($education)
                    ->except(['edu_attachment', 'file_name', 'id'])
                    ->toArray());
            } else {
                $this->education = $this->employee->educations()->create(collect($education)
                    ->except(['edu_attachment', 'file_name', 'id'])
                    ->toArray());
            }

            if ($education['edu_attachment'] instanceof TemporaryUploadedFile) {
                $this->education->addMedia($education['edu_attachment']->getRealPath())
                    ->usingFileName('edu-file-'.$index.'.'.$education['edu_attachment']->getClientOriginalExtension())
                    ->toMediaCollection('file', 'local');
            }
        }
    }
}
