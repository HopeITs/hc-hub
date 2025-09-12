<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Education;
use App\Models\Employee;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class EducationForm extends Form
{
    public ?Employee $employee = null;
    public ?Education $education = null;

    /** @var array<int, array{title:string|null, university:string|null, type:int|string|null, graduation_year:int|string|null}> */
    public array $educations = [];

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;

        // Seed current values
        $this->educations = $this->employee->educations()
            ->select('id','title', 'university', 'type', 'graduation_year')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'university' => $e->university,
                'type' => $e->type,
                'graduation_year' => $e->graduation_year,
                'edu_attachment' => $e->getFirstMediaUrl('file'),
                'file_name' => $e->getFirstMedia('file')->file_name ?? null,
            ])->toArray();

    }

    public function rules(): array
    {
        $currentYear = (int)now()->year;

        return [
            'educations' => ['array'],
            'educations.*.id' => ['nullable','exists:educations,id'],
            'educations.*.title' => ['required', 'string', 'max:255'],
            'educations.*.university' => ['required', 'string', 'max:255'],
            'educations.*.type' => ['nullable', 'integer', 'between:0,6'],
            'educations.*.graduation_year' => ['nullable', 'integer', 'digits:4', 'max:' . $currentYear],
            'educations.*.edu_attachment' => ['nullable'],
        ];
    }

    public function addEducation(): void
    {
        $this->educations[] = [
            'id' => null,
            'title' => '',
            'university' => '',
            'type' => null,
            'graduation_year' => null,
            'edu_attachment' => null,
            'file_name' => null,
        ];
    }

    public function removeEducation(int $index , $id = null): void
    {
        if (!array_key_exists($index, $this->educations)) return;

        unset($this->educations[$index]);

        if(isset($id)){
           Education::find($id)?->delete();
        }
        // Reindex so Blade indexes and array keys stay aligned
        $this->educations = array_values($this->educations);
    }

    public function save(): void
    {
        $this->validate();

        foreach ($this->educations as $index  => $education) {
            if(array_key_exists('id', $education) && isset($education['id'])) {
             $this->education = Education::find($education['id']);
             $this->education->update($education);
            }else{
                $education['employee_id'] = $this->employee->id;
                $this->education  = Education::create($education);
            }

            if ($education['edu_attachment'] instanceof TemporaryUploadedFile) {
                $this->education->addMedia($education['edu_attachment']->getRealPath())
                    ->usingFileName('edu-file-'.$index .'.' . $education['edu_attachment']->getClientOriginalExtension())
                    ->toMediaCollection('file');
            }

        }


    }
}
