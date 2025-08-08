<?php

namespace App\Livewire\Forms;

use App\Models\Employee;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class EmployeeForm extends Form
{
    public ?Employee $employee = null;

    #[Validate]
    public $photo;

    public array $name = [];
    public array $father_name = [];
    public array $mother_name = [];
    public array $address = [];
    public ?string $national_number = '';
    public ?string $actual_job = '';
    public float $years_of_service = 0;
    public ?string $work_email = '';
    public ?int $direct_manager_id = null;
    public ?int $tech_manager_id = null;
    public ?string $dob = '';
    public ?string $actual_dob = '';
    public ?string $civil_registry_office = '';
    public ?string $civil_registry_number = '';
    public ?string $place_of_registration = '';
    public int $gender = 0;
    public string $nationality = 'syrian';
    public int $marital_status = 0;
    public int $children = 0;
    public ?string $work_phone = '';
    public ?string $personal_phone = '';
    public ?string $personal_email = '';
    public ?string $skills = '';
    public ?string $interests = '';
    public bool $is_active = true;
    public bool $is_board_member = false;
    public ?string $board_member_started_at = null;
    public ?string $board_member_end_at = null;

    public function rules(): array
    {
        return [
            'photo' => ['nullable', 'image', 'max:2048'],
            'name.*' => ['required', 'string', 'max:255'],
            'national_number' => ['required', 'string', Rule::unique('employees', 'national_number')->ignore($this->employee)],
            'actual_job' => ['nullable', 'string', 'max:255'],
            'years_of_service' => ['nullable', 'numeric', 'min:0'],
            'work_email' => ['nullable', 'email', Rule::unique('employees', 'work_email')->ignore($this->employee)],
            'direct_manager_id' => ['nullable', 'exists:employees,id'],
            'tech_manager_id' => ['nullable', 'exists:employees,id'],
            'dob' => ['nullable', 'date'],
            'actual_dob' => ['nullable', 'date'],
            'civil_registry_office' => ['nullable', 'string', 'max:255'],
            'civil_registry_number' => ['nullable', 'string', 'max:255'],
            'place_of_registration' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:0,1,2'],
            'father_name.*' => ['nullable', 'string', 'max:255'],
            'mother_name.*' => ['nullable', 'string', 'max:255'],
            'nationality' => ['required', 'in:syrian'],
            'marital_status' => ['nullable', 'in:0,1,2'],
            'children' => ['nullable', 'integer', 'min:0'],
            'work_phone' => ['nullable', 'string', 'max:255'],
            'personal_phone' => ['nullable', 'string', 'max:255'],
            'personal_email' => ['nullable', 'email'],
            'address.*' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'string'],
            'interests' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
            'is_board_member' => ['required', 'boolean'],
            'board_member_started_at' => ['nullable', 'required_if:is_board_member,true', 'date'],
            'board_member_end_at' => ['nullable', 'date', 'after_or_equal:board_member_started_at'],
        ];
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
        $this->fill($employee->toArray());
        $this->name = $employee->getTranslations('name');
        $this->father_name = $employee->getTranslations('father_name');
        $this->mother_name = $employee->getTranslations('mother_name');
        $this->address = $employee->getTranslations('address');
        $this->is_board_member = (bool) $employee->is_board_member;
    }

    public function store(): void
    {
        $validated = $this->validate();
        if ($this->photo) {
            $validated['photo'] = $this->photo->store('employee_photos', 'public');
        }

        Employee::create($validated);
    }

    public function update(): void
    {
        $validated = $this->validate();

        if ($this->photo) {
            if ($this->employee->photo) {
                Storage::disk('public')->delete($this->employee->photo);
            }
            $validated['photo'] = $this->photo->store('employee_photos', 'public');
        }

        $this->employee->update($validated);
    }
}
