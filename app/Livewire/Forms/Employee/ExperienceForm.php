<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Livewire\WithFileUploads;

class ExperienceForm extends Form
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


    public function addExperience(): void
    {
        $this->experiences[] = ['job_title' => '', 'company' => '', 'start_date' => '', 'end_date' => '', 'place' => 'onsite', 'type' => ''];
    }

    public function removeExperience(int $index): void
    {
        unset($this->experiences[$index]);
        $this->experiences = array_values($this->experiences);
    }




    public function saveExperience(): void
    {
        $this->validate([
            'experiences' => 'array',
            'experiences.*.job_title' => 'required_with:experiences.*.company|string|max:255|nullable',
        ]);

        $this->employee->experiences()->delete();
        $this->employee->experiences()->createMany($this->experiences);
    }


}
