<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use Livewire\Form;

class CourseForm extends Form
{
    public ?Employee $employee = null;

    /** @var array<int, array{title:string|null, issuing_organization:string|null, date:string|null, is_approved:int|bool|null}> */
    public array $courses = [];

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;

        // Seed existing rows
        $this->courses = $employee->courses()
            ->select('title', 'issuing_organization', 'date', 'is_approved')
            ->get()
            ->map(fn ($c) => [
                'title'                => $c->title,
                'issuing_organization' => $c->issuing_organization,
                'date'                 => optional($c->date)->toDateString(), // Y-m-d for <input type="date">
                'is_approved'          => (int) $c->is_approved,
            ])->toArray();
    }

    public function rules(): array
    {
        return [
            'courses'                              => ['array'],
            'courses.*.title'                      => ['required', 'string', 'max:255'],
            'courses.*.issuing_organization'       => ['required', 'string', 'max:255'],
            'courses.*.date'                       => ['nullable', 'date'],
            'courses.*.is_approved'                => ['nullable', 'boolean'],
        ];
    }

    public function addCourse(): void
    {
        $this->courses[] = [
            'title'                => '',
            'issuing_organization' => '',
            'date'                 => null,
            'is_approved'          => 0,
        ];
    }

    public function removeCourse(int $index): void
    {
        if (! array_key_exists($index, $this->courses)) return;

        unset($this->courses[$index]);
        $this->courses = array_values($this->courses); // reindex for Livewire
    }

    public function save(): void
    {
        $this->validate();

        // Normalize & persist
        $payload = collect($this->courses)->map(function ($row) {
            return [
                'title'                => $row['title'],
                'issuing_organization' => $row['issuing_organization'],
                'date'                 => $row['date'] ?: null, // empty -> null
                'is_approved'          => (bool) ($row['is_approved'] ?? false),
            ];
        })->all();

        $this->employee->courses()->delete();
        $this->employee->courses()->createMany($payload);
    }
}
