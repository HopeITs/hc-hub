<?php

namespace App\Livewire\Forms;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Form;
use Livewire\WithFileUploads;

class EmployeeForm extends Form
{
    use WithFileUploads;

    public ?Employee $employee = null;
    public $photo;
    public $name = [];
    public $national_number;

    public $work_email_username;
    public $direct_manager_id;
    public $tech_manager_id;
    public $father_name = [];
    public $mother_name = [];
    public $dob;
    public $gender;
    public $marital_status;
    public $children;
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

    protected function buildWorkEmail(?string $username): ?string
    {
        $username = trim($username ?? '');
        return $username ? strtolower($username) . '@hcsyria.org' : null;
    }

    protected function extractUsernameFromEmail(?string $email): ?string
    {
        return $email ? explode('@', $email, 2)[0] ?? null : null;
    }

    public function setEmployee(Employee $employee)
    {
        $this->employee = $employee;
        $this->photo = $employee->photo;
        $this->national_number = $employee->national_number;
        $this->direct_manager_id = $employee->direct_manager_id;
        $this->tech_manager_id = $employee->tech_manager_id;
        $this->dob = $employee->dob;
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
        $this->board_member_started_at = $employee->board_member_started_at;
        $this->board_member_end_at = $employee->board_member_end_at;
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

        $this->work_email_username = $this->extractUsernameFromEmail($employee->email);
    }

    public function addEducation()
    {
        $this->educations[] = ['title' => '', 'university' => '', 'type' => '', 'graduation_year' => ''];
    }

    public function removeEducation(int $index)
    {
        unset($this->educations[$index]);
        $this->educations = array_values($this->educations);
    }

    public function addCourse()
    {
        $this->courses[] = ['title' => '', 'issuing_organization' => '', 'date' => '', 'is_approved' => 0];
    }

    public function removeCourse(int $index)
    {
        unset($this->courses[$index]);
        $this->courses = array_values($this->courses);
    }

    public function addExperience()
    {
        $this->experiences[] = ['job_title' => '', 'company' => '', 'start_date' => '', 'end_date' => '', 'place' => 'onsite', 'type' => ''];
    }

    public function removeExperience(int $index)
    {
        unset($this->experiences[$index]);
        $this->experiences = array_values($this->experiences);
    }

    public function addRelative()
    {
        $this->relatives[] = ['name' => '', 'phone' => '', 'relation' => ''];
    }

    public function removeRelative(int $index)
    {
        unset($this->relatives[$index]);
        $this->relatives = array_values($this->relatives);
    }

    public function addBadge()
    {
        $this->badges[] = ['evaluation_id' => null, 'currency' => 1, 'type' => '', 'date' => now()->format('Y-m-d'), 'reason' => '', 'current_salary' => null, 'new_salary' => null, 'notes' => ''];
    }

    public function removeBadge(int $index)
    {
        unset($this->badges[$index]);
        $this->badges = array_values($this->badges);
    }

    public function addChecklist()
    {
        $this->checklists[] = ['item_id' => null, 'brand' => '', 'model' => '', 'serial' => '', 'delivered_at' => now()->format('Y-m-d'), 'delivered_status' => '', 'returned_at' => null, 'returned_status' => '', 'notes' => ''];
    }

    public function removeChecklist(int $index)
    {
        unset($this->checklists[$index]);
        $this->checklists = array_values($this->checklists);
    }

    public function saveInformation()
    {
        $this->email = $this->buildWorkEmail($this->work_email_username);

        $this->validate([
            'name' => 'required|array',
            'name.*' => 'required|string|max:255',
            'national_number' => 'required|string|max:20',
            'is_active' => 'required|boolean',
            'nationality' => 'required|string|max:100',
            'email' => 'nullable|email|max:255|unique:users,email,' . $this->employee?->id,
            'work_email_username' => 'nullable|regex:/^[a-zA-Z0-9.\-_]+$/',
        ]);

        $data = $this->only(['name', 'national_number', 'is_active', 'nationality', 'direct_manager_id', 'tech_manager_id', 'dob', 'gender', 'marital_status', 'children', 'father_name', 'mother_name', 'civil_registry_office', 'civil_registry_number', 'place_of_registration', 'work_phone', 'personal_phone', 'personal_email', 'address', 'skills', 'interests', 'is_board_member', 'board_member_started_at', 'board_member_end_at']);
        $emailData = $this->only(['email']);
        if ($this->employee) {
            $this->employee->update($data);
            $this->employee->account()->update($emailData);
        } else {
            $this->employee = Employee::create($data);
            User::create([
                'email' => $emailData,
                'password' => Hash::make('HopeCenterHR'),
                'employee_id' => $this->employee->id,
            ]);
        }

        if ($this->photo && !is_string($this->photo)) {
            $this->employee->photo = $this->photo->store('employees', 'public');
            $this->employee->save();
        }
    }

    public function saveEducation()
    {
        $this->validate([
            'educations' => 'array',
            'educations.*.title' => 'required_with:educations.*.university|string|max:255|nullable',
        ]);

        $this->employee->educations()->delete();
        $this->employee->educations()->createMany($this->educations);
    }

    public function saveCourses()
    {
        $this->validate([
            'courses' => 'array',
            'courses.*.title' => 'required_with:courses.*.issuing_organization|string|max:255|nullable',
        ]);

        $this->employee->courses()->delete();
        $this->employee->courses()->createMany($this->courses);
    }

    public function saveExperience()
    {
        $this->validate([
            'experiences' => 'array',
            'experiences.*.job_title' => 'required_with:experiences.*.company|string|max:255|nullable',
        ]);

        $this->employee->experiences()->delete();
        $this->employee->experiences()->createMany($this->experiences);
    }

    public function saveRelatives()
    {
        $this->validate([
            'relatives' => 'array',
            'relatives.*.name' => 'required_with:relatives.*.relation|string|max:255|nullable',
        ]);

        $this->employee->relatives()->delete();
        $this->employee->relatives()->createMany($this->relatives);
    }

    public function saveBadges()
    {
        $this->validate([
            'badges' => 'array',
            'badges.*.type' => 'required_with:badges.*.date|string|max:255|nullable',
        ]);

        $this->employee->badges()->delete();
        $this->employee->badges()->createMany($this->badges);
    }

    public function saveChecklist()
    {
        $this->validate([
            'checklists' => 'array',
            'checklists.*.item_id' => 'required_with:checklists.*.brand|exists:checklist_items,id|nullable',
        ]);

        $this->employee->checklists()->delete();
        $this->employee->checklists()->createMany($this->checklists);
    }
}
