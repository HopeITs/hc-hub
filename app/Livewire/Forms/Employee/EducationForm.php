<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use Illuminate\Validation\Rule;
use Livewire\Form;

class EducationForm extends Form
{
    public ?Employee $employee = null;

    /** @var array<int, array{title:string|null, university:string|null, type:int|string|null, graduation_year:int|string|null}> */
    public array $educations = [];

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;

        // Seed current values
        $this->educations = $this->employee->educations()
            ->select('title', 'university', 'type', 'graduation_year')
            ->get()
            ->map(fn ($e) => [
                'title'           => $e->title,
                'university'      => $e->university,
                'type'            => $e->type,
                'graduation_year' => $e->graduation_year,
            ])->toArray();
    }

    public function rules(): array
    {
        $currentYear = (int) now()->year;

        return [
            'educations'                         => ['array'],
            'educations.*.title'                 => ['required','string','max:255'],
            'educations.*.university'            => ['required','string','max:255'],
            'educations.*.type'                  => ['nullable','integer','between:0,6'],
            'educations.*.graduation_year'       => ['nullable','integer','digits:4','max:'.$currentYear],
        ];
    }

    public function addEducation(): void
    {
        $this->educations[] = [
            'title'           => '',
            'university'      => '',
            'type'            => null,
            'graduation_year' => null,
        ];
    }

    public function removeEducation(int $index): void
    {
        if (! array_key_exists($index, $this->educations)) return;

        unset($this->educations[$index]);
        // Reindex so Blade indexes and array keys stay aligned
        $this->educations = array_values($this->educations);
    }

    public function save(): void
    {
        $this->validate();

        // Normalize empties to null to match DB
        $payload = collect($this->educations)->map(function ($row) {
            return [
                'title'           => $row['title'],
                'university'      => $row['university'],
                'type'            => $row['type'] === '' ? null : $row['type'],
                'graduation_year' => $row['graduation_year'] === '' ? null : $row['graduation_year'],
            ];
        })->all();

        $this->employee->educations()->delete();
        $this->employee->educations()->createMany($payload);
    }
}
