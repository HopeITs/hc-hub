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
            'name' => 'required|array',
            'name.*' => 'required|string|max:255',
            'national_number' => 'required|string|max:20',
            'nationality' => 'required|in:syrian',
            'is_active' => 'required|boolean',
            'dob' => 'nullable|date_format:Y-m-d',
            'board_member_started_at' => 'nullable|date_format:Y-m-d',
            'board_member_end_at' => 'nullable|date_format:Y-m-d|after_or_equal:board_member_started_at',
            'gender' => 'nullable|in:0,1,2',
            'marital_status' => 'nullable|in:0,1,2',
            'children' => 'nullable|integer|min:0',
            'direct_manager_id' => 'nullable|exists:employees,id',
            'tech_manager_id' => 'nullable|exists:employees,id',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore(optional($this->employee?->account)->id)],
            'work_email_username' => 'nullable|regex:/^[a-zA-Z0-9.\-_]+$/',
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

    public function addEducation(): void
    {
        $this->educations[] = ['title' => '', 'university' => '', 'type' => '', 'graduation_year' => ''];
    }

    public function removeEducation(int $index): void
    {
        unset($this->educations[$index]);
        $this->educations = array_values($this->educations);
    }

    public function addCourse(): void
    {
        $this->courses[] = ['title' => '', 'issuing_organization' => '', 'date' => '', 'is_approved' => 0];
    }

    public function removeCourse(int $index): void
    {
        unset($this->courses[$index]);
        $this->courses = array_values($this->courses);
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

    public function addRelative(): void
    {
        $this->relatives[] = ['name' => '', 'phone' => '', 'relation' => ''];
    }

    public function removeRelative(int $index): void
    {
        unset($this->relatives[$index]);
        $this->relatives = array_values($this->relatives);
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

    public function addChecklist(): void
    {
        $this->checklists[] = [
            'item_id' => null,
            'brand' => '',
            'model' => '',
            'serial' => '',
            'delivered_at' => now()->format('Y-m-d'),
            'delivered_status' => '',
            'returned_at' => null,
            'returned_status' => '',
            'notes' => '',
        ];
    }

    public function removeChecklist(int $index): void
    {
        unset($this->checklists[$index]);
        $this->checklists = array_values($this->checklists);
    }

    public function saveInformation(): Employee
    {
        $this->email = $this->buildWorkEmail($this->work_email_username);
//dd($this->all());
        $this->validate();

        $data = [
            'name' => $this->name,
            'national_number' => $this->national_number,
            'is_active' => (bool) $this->is_active,
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
            'is_board_member' => (bool) $this->is_board_member,
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

    public function saveEducation(): void
    {
        $this->validate([
            'educations' => 'array',
            'educations.*.title' => 'required_with:educations.*.university|string|max:255|nullable',
        ]);

        $this->employee->educations()->delete();
        $this->employee->educations()->createMany($this->educations);
    }

    public function saveCourses(): void
    {
        $this->validate([
            'courses' => 'array',
            'courses.*.title' => 'required_with:courses.*.issuing_organization|string|max:255|nullable',
        ]);

        $this->employee->courses()->delete();
        $this->employee->courses()->createMany($this->courses);
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

    public function saveRelatives(): void
    {
        $this->validate([
            'relatives' => 'array',
            'relatives.*.name' => 'required_with:relatives.*.relation|string|max:255|nullable',
        ]);

        $this->employee->relatives()->delete();
        $this->employee->relatives()->createMany($this->relatives);
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

    public function saveChecklist(): void
    {
        $this->validate([
            'checklists' => 'array',
            'checklists.*.item_id' => 'required_with:checklists.*.brand|exists:checklist_items,id|nullable',
        ]);

        $this->employee->checklists()->delete();
        $this->employee->checklists()->createMany($this->checklists);
    }
}
