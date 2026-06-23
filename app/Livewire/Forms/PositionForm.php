<?php

namespace App\Livewire\Forms;

use App\Models\Contract;
use App\Enums\SalaryPer;
use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PositionForm extends Form
{
    public ?Position $position = null;


    public $department_id;
    public $job_level_id;
    public $program_id;
    public $tech_manager_id;
    public $direct_manager_id;
    public $center_id;
    public $city_id;
    public $title_en = '';
    public $title_ar = '';
    public $qualifications;
    public $max_emp;
    public $responsibilities;
    public  $is_active = true;

    protected function rules()
    {
        return [
            'title_en' => ['nullable', 'min:3', 'max:255'],
            'title_ar' => ['nullable', 'min:3', 'max:255'],
            'program_id'       => 'required|exists:programs,id',
            'center_id'        => 'nullable|exists:centers,id',
            'city_id'          => 'required|exists:cities,id',
            'tech_manager_id'  => 'nullable|exists:positions,id',
            'direct_manager_id'  => 'nullable|exists:positions,id',
            'job_level_id'     => 'required|exists:job_levels,id',
            'department_id'    => 'required|exists:departments,id',
            'qualifications'   => 'required|string',
            'responsibilities' => 'required|string',
            'max_emp'          => 'required|integer|min:1',


        ];
    }
    public function setPosition(Position $position): void
    {
        $this->position = $position;
        $this->title_en = $position->title_en;
        $this->title_ar = $position->title_ar;
        $this->city_id = $position->city_id;
        $this->center_id = $position->center_id;
        $this->program_id = $position->program_id;
        $this->department_id = $position->department_id;
        $this->job_level_id = $position->job_level_id;
        $this->tech_manager_id = $position->tech_manager_id;
        $this->direct_manager_id = $position->direct_manager_id;
        $this->is_active = $position->is_active;
        $this->max_emp = $position->max_emp;
        $this->responsibilities = $position->responsibilities;
        $this->qualifications = $position->qualifications;

    }

    public function store()
    {
        $this->normalizeNullableManagers();
        $this->validate();
        $this->position = Position::create($this->pull());
        $this->reset();

    }

    public function update()
    {
        $this->normalizeNullableManagers();
        $this->validate();

        $this->position->update(
            $this->all()
        );

        $this->reset();
    }

    public function statusToggle(){
        $this->program->update(
            [
                'is_active' => !$this->position->is_active,
            ]
        );
    }

    private function normalizeNullableManagers(): void
    {
        // An empty <select> submits '' — coerce to null so "online" positions
        // (no center) and unset managers store/validate as null, not ''.
        $this->tech_manager_id = blank($this->tech_manager_id) ? null : $this->tech_manager_id;
        $this->direct_manager_id = blank($this->direct_manager_id) ? null : $this->direct_manager_id;
        $this->center_id = blank($this->center_id) ? null : $this->center_id;
    }
}
