<?php

namespace App\Livewire\Forms;

use App\Models\Employee;
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
        $username = $username !== null ? trim($username) : null;
        if ($username === null || $username === '') {
            return null;
        }
        return strtolower($username) . '@hcsyria.org';
    }

    protected function extractUsernameFromEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }
        return explode('@', $email, 2)[0] ?? null;
    }

    public function setEmployee(Employee $employee)
    {
        $this->employee = $employee;
        $this->fill($employee->toArray());
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
        $this->work_email_username = $this->extractUsernameFromEmail($employee->work_email);
    }

    public function addEducation() { $this->educations[] = ['title' => '', 'university' => '', 'type' => '', 'graduation_year' => '']; }
    public function removeEducation(int $index) { unset($this->educations[$index]); $this->educations = array_values($this->educations); }
    public function addCourse() { $this->courses[] = ['title' => '', 'issuing_organization' => '', 'date' => '', 'is_approved' => 0]; }
    public function removeCourse(int $index) { unset($this->courses[$index]); $this->courses = array_values($this->courses); }
    public function addExperience() { $this->experiences[] = ['job_title' => '', 'company' => '', 'start_date' => '', 'end_date' => '', 'place' => 'onsite', 'type' => '']; }
    public function removeExperience(int $index) { unset($this->experiences[$index]); $this->experiences = array_values($this->experiences); }
    public function addRelative() { $this->relatives[] = ['name' => '', 'phone' => '', 'relation' => '']; }
    public function removeRelative(int $index) { unset($this->relatives[$index]); $this->relatives = array_values($this->relatives); }
    public function addBadge() { $this->badges[] = ['evaluation_id' => null, 'currency' => 1, 'type' => '', 'date' => now()->format('Y-m-d'), 'reason' => '', 'current_salary' => null, 'new_salary' => null, 'notes' => '']; }
    public function removeBadge(int $index) { unset($this->badges[$index]); $this->badges = array_values($this->badges); }
    public function addChecklist() { $this->checklists[] = ['item_id' => null, 'brand' => '', 'model' => '', 'serial' => '', 'delivered_at' => now()->format('Y-m-d'), 'delivered_status' => '', 'returned_at' => null, 'returned_status' => '', 'notes' => '']; }
    public function removeChecklist(int $index) { unset($this->checklists[$index]); $this->checklists = array_values($this->checklists); }

    public function rules()
    {
        $this->work_email = $this->buildWorkEmail($this->work_email_username);

        return [
            'name' => 'required|array', 'name.*' => 'required|string|max:255',
            'national_number' => 'required|string|max:20',
            'is_active' => 'required|boolean',
            'nationality' => 'required|string|max:100',
            'work_email' => 'nullable|email|max:255|unique:employees,work_email,' . $this->employee?->id,
            'work_email_username' => 'nullable|regex:/^[a-zA-Z0-9.\-_]+$/',
            'educations' => 'nullable|array',
            'educations.*.title' => 'required_with:educations.*.university|string|max:255|nullable',
            'courses' => 'nullable|array',
            'courses.*.title' => 'required_with:courses.*.issuing_organization|string|max:255|nullable',
            'experiences' => 'nullable|array',
            'experiences.*.job_title' => 'required_with:experiences.*.company|string|max:255|nullable',
            'relatives' => 'nullable|array',
            'relatives.*.name' => 'required_with:relatives.*.relation|string|max:255|nullable',
            'badges' => 'nullable|array',
            'badges.*.type' => 'required_with:badges.*.date|string|max:255|nullable',

            'checklists' => 'nullable|array',
            'checklists.*.item_id' => 'required_with:checklists.*.brand|exists:checklist_items,id|nullable',
            'checklists.*.brand' => 'nullable|string|max:255',
            'checklists.*.model' => 'nullable|string|max:255',
            'checklists.*.serial' => 'nullable|string|max:255',
            'checklists.*.delivered_at' => 'nullable|date',
            'checklists.*.delivered_status' => 'nullable|string|max:255',
            'checklists.*.returned_at' => 'nullable|date|after_or_equal:checklists.*.delivered_at',
            'checklists.*.returned_status' => 'nullable|string|max:255',
            'checklists.*.notes' => 'nullable|string',
        ];
    }

    private function cleanupEmptyRelations()
    {
        $this->educations = collect($this->educations)->filter(fn ($edu) => !empty($edu['title']) || !empty($edu['university']))->values()->all();
        $this->courses = collect($this->courses)->filter(fn ($course) => !empty($course['title']) || !empty($course['issuing_organization']))->values()->all();
        $this->experiences = collect($this->experiences)->filter(fn ($exp) => !empty($exp['job_title']) || !empty($exp['company']))->values()->all();
        $this->relatives = collect($this->relatives)->filter(fn ($rel) => !empty($rel['name']) || !empty($rel['relation']))->values()->all();
        $this->badges = collect($this->badges)->filter(fn ($badge) => !empty($badge['type']))->values()->all();
        $this->checklists = collect($this->checklists)->filter(fn ($item) => !empty($item['item_id']))->values()->all();
    }

    public function store()
    {
        $this->cleanupEmptyRelations();
        $this->validate();
        $data = $this->all();
        unset($data['work_email_username']);

        $employee = Employee::create($data);

        if (!empty($this->educations)) { $employee->educations()->createMany($this->educations); }
        if (!empty($this->courses)) { $employee->courses()->createMany($this->courses); }
        if (!empty($this->experiences)) { $employee->experiences()->createMany($this->experiences); }
        if (!empty($this->relatives)) { $employee->relatives()->createMany($this->relatives); }
        if (!empty($this->badges)) { $employee->badges()->createMany($this->badges); }
        if (!empty($this->checklists)) { $employee->checklists()->createMany($this->checklists); }

        if ($this->photo) {
            $employee->photo = $this->photo->store('employees', 'public');
            $employee->save();
        }
        $this->reset();
    }

    public function update()
    {
        $this->cleanupEmptyRelations();
        $this->validate();
        $data = $this->all();
        unset($data['work_email_username']);

        $this->employee->update($data);

        $this->employee->educations()->delete();
        if (!empty($this->educations)) { $this->employee->educations()->createMany($this->educations); }

        $this->employee->courses()->delete();
        if (!empty($this->courses)) { $this->employee->courses()->createMany($this->courses); }

        $this->employee->experiences()->delete();
        if (!empty($this->experiences)) { $this->employee->experiences()->createMany($this->experiences); }

        $this->employee->relatives()->delete();
        if (!empty($this->relatives)) { $this->employee->relatives()->createMany($this->relatives); }

        $this->employee->badges()->delete();
        if (!empty($this->badges)) { $this->employee->badges()->createMany($this->badges); }

        $this->employee->checklists()->delete();
        if (!empty($this->checklists)) { $this->employee->checklists()->createMany($this->checklists); }

        if ($this->photo && !is_string($this->photo)) {
            $this->employee->photo = $this->photo->store('employees', 'public');
            $this->employee->save();
        }
    }
}
