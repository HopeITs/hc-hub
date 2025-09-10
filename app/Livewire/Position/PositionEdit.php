<?php

namespace App\Livewire\Position;

use App\Livewire\Forms\PositionForm;
use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use App\Traits\WithAlerts;
use Livewire\Component;

class PositionEdit extends Component
{
    use WithAlerts;
    public PositionForm $form;
    public ?Position $position;
    public function render()
    {
        $this->form->setPosition($this->position);
        $cities = City::where('is_active', true)->get();
        $programs = Program::where('is_active', true)->get();
        $centers = Center::where('is_active', true)->with('city')->get();
        $jobLevels = JobLevel::orderBy('grade')->get();

        $departments = Department::all();

        $title = $this->form->position->title;
        return view('livewire.position.form',compact('title','cities','programs','centers','jobLevels','departments'));

    }

    public function save()
    {
        $this->form->update();
        $this->successToast(__('general.success-message'));
        return redirect()->route('positions.index');
    }
}
