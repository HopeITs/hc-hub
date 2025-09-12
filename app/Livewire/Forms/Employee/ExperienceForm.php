<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use App\Models\Experience;
use Livewire\Form;

class ExperienceForm extends Form
{
    public ?Employee $employee = null;
    public ?Experience $experience = null;
    public array $experiences = [];

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;

        $this->experiences = $this->employee->experiences()
            ->select('id', 'job_title', 'company', 'start_date', 'end_date', 'place', 'type')
            ->get()
            ->map(fn(Experience $exp) => [
                'id' => $exp->id,
                'job_title' => $exp->job_title,
                'company' => $exp->company,
                'start_date' => $exp->start_date?->format('Y-m-d'),
                'end_date' => $exp->end_date?->format('Y-m-d'),
                'place' => $exp->place,
                'type' => $exp->type,
            ])->toArray();
    }

    public function rules(): array
    {
        return [
            'experiences' => ['array'],
            'experiences.*.id' => ['nullable', 'exists:experiences,id'],
            'experiences.*.job_title' => ['required', 'string', 'max:255'],
            'experiences.*.company' => ['required', 'string', 'max:255'],
            'experiences.*.start_date' => ['required', 'date'],
            'experiences.*.end_date' => ['nullable', 'date', 'after_or_equal:experiences.*.start_date'],
            'experiences.*.place' => ['integer'],
            'experiences.*.type' => [ 'integer'],
        ];
    }

    public function addExperience(): void
    {
        $this->experiences[] = [
            'id' => null,
            'job_title' => '',
            'company' => '',
            'start_date' => null,
            'end_date' => null,
            'place' => '',
            'type' => '',
        ];
    }

    public function removeExperience(int $index, ?int $id = null): void
    {
        if (!array_key_exists($index, $this->experiences)) {
            return;
        }

        unset($this->experiences[$index]);

        if (isset($id)) {
            Experience::find($id)?->delete();
        }

        $this->experiences = array_values($this->experiences);
    }

    public function save(): void
    {
        $this->validate();

        foreach ($this->experiences as $experienceData) {
            if(array_key_exists('id', $experienceData) && isset($experienceData['id'])) {
                $this->experience = Experience::find($experienceData['id']);
                $this->experience->update($experienceData);
            }else{
                $experienceData['employee_id'] = $this->employee->id;
                $this->experience  = Experience::create($experienceData);
            }

        }
    }
}
