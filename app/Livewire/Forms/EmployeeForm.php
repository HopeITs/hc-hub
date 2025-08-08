<?php

namespace App\Livewire\Forms;

use App\Models\Employee;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Livewire\WithFileUploads;

class EmployeeForm extends Form
{
    use WithFileUploads;

    public ?Employee $employee = null;

    public $photo;
    public $name = [];
    public $national_number;
    public $actual_job;
    public $years_of_service;
    public $work_email;
    public $direct_manager_id;
    public $tech_manager_id;
    public $father_name = [];
    public $mother_name = [];
    public $dob;
    public $gender;
    public $marital_status;
    public $children;
    public $nationality;
    public $civil_registry_office;
    public $civil_registry_number;
    public $place_of_registration;
    public $work_phone;
    public $personal_phone;
    public $personal_email;
    public $address = [];
    public $skills;
    public $interests;
    public $is_active = 1;
    public $is_board_member = false;
    public $board_member_started_at;
    public $board_member_end_at;

    // Education
    public $education_title;
    public $education_university;
    public $education_type;
    public $education_graduation_year;

    public function setEmployee(Employee $employee)
    {
        $this->employee = $employee;

        $this->fill($employee->toArray());
        $this->name = $employee->getTranslations('name');
        $this->father_name = $employee->getTranslations('father_name');
        $this->mother_name = $employee->getTranslations('mother_name');
        $this->address = $employee->getTranslations('address');
    }

    public function rules()
    {
        return [
            'name' => 'required|array',
            'name.*' => 'required|string|max:255',
            'national_number' => 'required|string|max:20',
            'actual_job' => 'nullable|string|max:255',
            'years_of_service' => 'nullable|numeric|min:0',
            'work_email' => 'nullable|email|max:255',
            'direct_manager_id' => 'nullable|exists:employees,id',
            'tech_manager_id' => 'nullable|exists:employees,id',
            'photo' => 'nullable|image|max:2048',

            'father_name' => 'nullable|array',
            'mother_name' => 'nullable|array',
            'dob' => 'nullable|date',
            'gender' => 'nullable|integer|in:0,1,2',
            'marital_status' => 'nullable|integer|in:0,1,2',
            'children' => 'nullable|integer|min:0',
            'nationality' => 'nullable|string|max:100',
            'civil_registry_office' => 'nullable|string|max:255',
            'civil_registry_number' => 'nullable|string|max:255',
            'place_of_registration' => 'nullable|string|max:255',
            'work_phone' => 'nullable|string|max:50',
            'personal_phone' => 'nullable|string|max:50',
            'personal_email' => 'nullable|email|max:255',
            'address' => 'nullable|array',
            'skills' => 'nullable|string|max:255',
            'interests' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            'is_board_member' => 'nullable|boolean',
            'board_member_started_at' => 'nullable|date',
            'board_member_end_at' => 'nullable|date|after_or_equal:board_member_started_at',

            // Education
            'education_title' => 'nullable|string|max:255',
            'education_university' => 'nullable|string|max:255',
            'education_type' => 'nullable|integer|in:0,1,2,3,4,5,6',
            'education_graduation_year' => 'nullable|digits:4|integer',
        ];
    }

    public function store()
    {
        $data = $this->validate();

        $employee = Employee::create($data);

        if ($this->photo) {
            $employee->photo = $this->photo->store('employees', 'public');
            $employee->save();
        }

        $this->reset();
    }

    public function update()
    {
        $data = $this->validate();

        $this->employee->update($data);

        if ($this->photo && !is_string($this->photo)) {
            $this->employee->photo = $this->photo->store('employees', 'public');
            $this->employee->save();
        }
    }
}
