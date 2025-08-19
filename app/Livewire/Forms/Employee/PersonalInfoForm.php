<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use Livewire\Attributes\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Livewire\WithFileUploads;

class PersonalInfoForm extends Form
{
    use WithFileUploads;

    public ?Employee $employee = null;

    public $photo = null;
    #[Validate(['name' => 'required|array', 'name.*' => 'required|string'])]
    public $name = [];

    #[Validate(['father_name' => 'nullable|array', 'father_name.*' => 'nullable|string'])]
    public $father_name = [];

    #[Validate(['mother_name' => 'nullable|array', 'mother_name.*' => 'nullable|string'])]
    public $mother_name = [];

    #[Validate(['address' => 'nullable|array', 'address.*' => 'nullable|string'])]
    public $address = [];

    #[Validate('nullable|date')]
    public $dob;

    #[Validate('required|in:1,2')]
    public $gender;

    #[Validate('required')]
    public $marital_status;

    #[Validate('nullable|integer|min:0')]
    public $children;

    #[Validate('required|string')]
    public $nationality;

    #[Validate('nullable|string')]
    public $civil_registry_office;

    #[Validate('nullable|string')]
    public $civil_registry_number;

    #[Validate('nullable|string')]
    public $place_of_registration;

    #[Validate('nullable|string')]
    public $work_phone;

    #[Validate('nullable|string')]
    public $personal_phone;

    #[Validate('nullable|email')]
    public $personal_email;

    #[Validate('nullable|string')]
    public $skills;

    #[Validate('nullable|string')]
    public $interests;

    #[Validate('boolean')]
    public $is_board_member;

    #[Validate('nullable|date')]
    public $board_member_started_at;

    #[Validate('nullable|date|after:board_member_started_at')]
    public $board_member_end_at;

    public $work_email_username;

    protected function buildWorkEmail(?string $username): ?string
    {
        $username = trim($username ?? '');
        return $username ? strtolower($username) . '@hcsyria.org' : null;
    }

    protected function extractUsernameFromEmail(?string $email): ?string
    {
        return $email ? explode('@', $email, 2)[0] ?? null : null;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
        $this->photo = $employee->photo;
        $this->national_number = $employee->national_number;
        $this->direct_manager_id = $employee->direct_manager_id;
        $this->tech_manager_id = $employee->tech_manager_id;
        $this->dob = $employee->dob?->format('Y-m-d');
        $this->gender = $employee->gender;
        $this->marital_status = $employee->marital_status;
        $this->children = $employee->children;
        $this->nationality = $employee->nationality;
        $this->civil_registry_office = $employee->civil_registry_office;
        $this->civil_registry_number = $employee->civil_registry_number;
        $this->place_of_registration = $employee->place_of_registration;
        $this->work_phone = $employee->work_phone;
        $this->personal_phone = $employee->personal_phone;
        $this->personal_email = $employee->personal_email;
        $this->skills = $employee->skills;
        $this->interests = $employee->interests;
        $this->is_active = $employee->is_active;
        $this->is_board_member = $employee->is_board_member;
        $this->board_member_started_at = $employee->board_member_started_at?->format('Y-m-d');
        $this->board_member_end_at = $employee->board_member_end_at?->format('Y-m-d');
        $this->name = $employee->getTranslations('name');
        $this->father_name = $employee->getTranslations('father_name');
        $this->mother_name = $employee->getTranslations('mother_name');
        $this->address = $employee->getTranslations('address');
        $this->educations = $employee->educations()->get()->toArray();
        $this->courses = $employee->courses()->get()->toArray();
        $this->experiences = $employee->experiences()->get()->toArray();
        $this->relatives = $employee->relatives()->get()->toArray();
        $this->badges = $employee->badges()->get()->toArray();
        $this->checklists = $employee->checklists()->get()->toArray();
        $this->work_email_username = $this->extractUsernameFromEmail($employee->account?->email);
    }


    public function saveInformation(): Employee
    {
        $this->email = $this->buildWorkEmail($this->work_email_username);

        $this->validate();
        dd($this->all());
        $data = [
            'name' => $this->name,
            'national_number' => $this->national_number,
            'is_active' => (bool)$this->is_active,
            'nationality' => $this->nationality,
            'direct_manager_id' => $this->direct_manager_id,
            'tech_manager_id' => $this->tech_manager_id,
            'dob' => $this->dob ?: null,
            'gender' => $this->gender,
            'marital_status' => $this->marital_status,
            'children' => $this->children,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'civil_registry_office' => $this->civil_registry_office,
            'civil_registry_number' => $this->civil_registry_number,
            'place_of_registration' => $this->place_of_registration,
            'work_phone' => $this->work_phone,
            'personal_phone' => $this->personal_phone,
            'personal_email' => $this->personal_email,
            'address' => $this->address,
            'skills' => $this->skills,
            'interests' => $this->interests,
            'is_board_member' => (bool)$this->is_board_member,
            'board_member_started_at' => $this->board_member_started_at ?: null,
            'board_member_end_at' => $this->board_member_end_at ?: null,
        ];

        if ($this->employee) {
            $this->employee->update($this->except('email'));

            if ($this->employee->account) {
                $this->employee->account->update(['email' => $this->email]);
            }
        } else {
            $this->employee = Employee::create($this->except('email'));

            User::create([
                'email' => $this->email,
                'password' => Hash::make('HopeCenterHR'),
                'employee_id' => $this->employee->id,
            ]);
        }

        if ($this->photo && !is_string($this->photo)) {
            $this->employee->photo = $this->photo->store('employees', 'public');
            $this->employee->save();
        }
        return $this->employee;
    }


}
