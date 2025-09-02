<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Livewire\WithFileUploads;

class BadgeForm extends Form
{
    use WithFileUploads;

    public ?Employee $employee = null;

    public $photo;

    #[Validate('required|array')]
    public $name = [];
    #[Validate('required|array')]
    public $national_number;
    public $email;
    public $work_email_username;
    public $direct_manager_id;
    public $tech_manager_id;
    public $father_name = [];
    public $mother_name = [];
    public $dob;
    public $gender;
    public $marital_status;
    public $children = 0;
    public $nationality = 'syrian';
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
    public array $educations = [];
    public array $courses = [];
    public array $experiences = [];
    public array $relatives = [];
    public array $badges = [];
    public array $checklists = [];

    protected function rules()
    {
        return [

        ];
    }


    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;

    }


    public function addBadge(): void
    {
        $this->badges[] = [
            'evaluation_id' => null,
            'currency' => 1,
            'type' => '',
            'date' => now()->format('Y-m-d'),
            'reason' => '',
            'current_salary' => null,
            'new_salary' => null,
            'notes' => '',
        ];
    }

    public function removeBadge(int $index): void
    {
        unset($this->badges[$index]);
        $this->badges = array_values($this->badges);
    }


    public function saveBadges(): void
    {
        $this->validate([
            'badges' => 'array',
            'badges.*.type' => 'required_with:badges.*.date|string|max:255|nullable',
        ]);

        $this->employee->badges()->delete();
        $this->employee->badges()->createMany($this->badges);
    }

}
