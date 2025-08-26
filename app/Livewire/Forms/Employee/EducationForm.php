<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Livewire\WithFileUploads;

class EducationForm extends Form
{
    use WithFileUploads;

    public ?Employee $employee = null ;


    protected function rules()
    {
        return [

        ];
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;

    }

    public function addEducation(): void
    {
        $this->educations[] = ['title' => '', 'university' => '', 'type' => '', 'graduation_year' => ''];
    }

    public function removeEducation(int $index): void
    {
        unset($this->educations[$index]);
        $this->educations = array_values($this->educations);
    }



    public function saveEducation(): void
    {
        $this->validate([
            'educations' => 'array',
            'educations.*.title' => 'required_with:educations.*.university|string|max:255|nullable',
        ]);

        $this->employee->educations()->delete();
        $this->employee->educations()->createMany($this->educations);
    }

}
