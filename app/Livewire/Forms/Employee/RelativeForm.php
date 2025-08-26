<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Livewire\WithFileUploads;

class RelativeForm extends Form
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


    public function addRelative(): void
    {
        $this->relatives[] = ['name' => '', 'phone' => '', 'relation' => ''];
    }

    public function removeRelative(int $index): void
    {
        unset($this->relatives[$index]);
        $this->relatives = array_values($this->relatives);
    }


    public function saveRelatives(): void
    {
        $this->validate([
            'relatives' => 'array',
            'relatives.*.name' => 'required_with:relatives.*.relation|string|max:255|nullable',
        ]);

        $this->employee->relatives()->delete();
        $this->employee->relatives()->createMany($this->relatives);
    }

}
