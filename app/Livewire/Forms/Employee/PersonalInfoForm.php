<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;
use Livewire\WithFileUploads;

class PersonalInfoForm extends Form
{
    use WithFileUploads;

    public ?Employee $employee = null;

    public $photo = null;
    #[Validate('required|string')]
    public $name_ar ;
    #[Validate('required|string')]
    public $name_en ;

    public $id;
    #[Validate('required|string')]
    public $father_name_ar ;
    #[Validate('required|string')]
    public $father_name_en ;

    #[Validate('required|string')]
    public $mother_name_ar ;
    #[Validate('required|string')]
    public $mother_name_en ;
    #[Validate('nullable|string')]
    public $address_ar ;
    #[Validate('nullable|string')]
    public $address_en ;


    public $email;


    public $national_number;

    #[Validate('nullable|date')]
    public $dob;

    #[Validate('nullable|date')]
    public $actual_dob;

    #[Validate('required|in:1,2')]
    public $gender;

    #[Validate('required')]
    public $marital_status;

    #[Validate('nullable|integer|min:0')]
    public $children;

    #[Validate('integer')]
    public $nationality;

    #[Validate('nullable|string')]
    public $civil_registry_office;

    #[Validate('nullable|string')]
    public $civil_registry_number;
    #[Validate('nullable|string')]
    public $place_and_birth_date;
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
    public $is_board_member = false;

    #[Validate('nullable|date')]
    public $board_member_started_at;

    #[Validate('nullable|date|after:board_member_started_at')]
    public $board_member_end_at;
    #[Validate('boolean')]
    public $is_active = true;


    public $username;

    #[Validate('nullable|exists:employees,id')]
    public $direct_manager_id = null;
    #[Validate('nullable|exists:employees,id')]
    public $tech_manager_id = null;

    public $city_id;

    public $roles;


    public function rules()
    {
        return [
            'national_number' => [
                'required',
                Rule::unique('employees', 'national_number')->ignore($this->employee?->id),
            ],
            'email' => [
                'required',
                Rule::unique('users', 'email')->ignore($this->employee?->account->id),
            ],
            'username' => [
                'required',
                Rule::unique('employees', 'username')->ignore($this->employee?->id),
            ],
        ];
    }


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
        $this->id = $employee->id;
        $this->city_id = $employee->city_id;
        $this->national_number = $employee->national_number;
        $this->direct_manager_id = $employee->direct_manager_id;
        $this->tech_manager_id = $employee->tech_manager_id;
        $this->actual_dob = $employee->actual_dob?->format('Y-m-d');
        $this->dob = $employee->dob?->format('Y-m-d');
        $this->gender = $employee->gender;
        $this->marital_status = $employee->marital_status;
        $this->children = $employee->children;
        $this->nationality = $employee->nationality;
        $this->civil_registry_office = $employee->civil_registry_office;
        $this->place_and_birth_date = $employee->place_and_birth_date;
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
        $this->name_ar = $employee->name_ar;
        $this->name_en = $employee->name_en;
        $this->father_name_en = $employee->father_name_en;
        $this->father_name_ar = $employee->father_name_ar;
        $this->mother_name_en = $employee->mother_name_en;
        $this->mother_name_ar = $employee->mother_name_ar;
        $this->address_en = $employee->address_en;
        $this->address_ar = $employee->address_ar;
        $this->username = $employee->username;
        $this->email = $employee->account?->email;
        $this->photo = $employee->getFirstMediaUrl('avatar');
        $this->roles = implode(',' ,$employee->account?->roles()->pluck('name')->toArray()??[]);

    }


    public function saveInformation(): Employee
    {

        $this->email = $this->buildWorkEmail($this->username);

        $this->validate();

        if ($this->employee) {
            $this->employee->update($this->except(['email', 'photo']));

            if ($this->employee->account) {
                $this->employee->account->update(['email' => $this->email]);
                $this->employee->account->syncRoles(json_decode($this->roles));
            }

        } else {
            $this->city_id = auth()->user()->profile?->city_id ?? 1;
            $this->employee = Employee::create($this->except(['email', 'photo']));

          $user =  User::create([
                'email' => $this->email,
                'password' => Hash::make('HopeCenterHR'),
                'employee_id' => $this->employee->id,
            ]);

          $user->assignRole(json_decode($this->roles));
        }

        if ($this->photo instanceof TemporaryUploadedFile) {
            $this->employee->addMedia($this->photo->getRealPath())
                ->usingFileName('employee-avatar.' . $this->photo->getClientOriginalExtension())
                ->toMediaCollection('avatar');
        }


        return $this->employee;
    }


}
