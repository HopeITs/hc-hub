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
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class PositionEdit extends Component
{
    use WithAlerts;

    public PositionForm $form;

    public ?Position $position;

    public function mount(Position $position): void
    {
        Gate::authorize('update', $position);
        $this->position = $position;
        $this->form->setPosition($position);
    }

    public function render()
    {
        $activeCityId = auth()->user()->activeCityId();
        $cities = City::where('is_active', true)
            ->when($activeCityId, fn ($query) => $query->whereKey($activeCityId))
            ->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        $centers = Center::where('is_active', true)
            ->when($activeCityId, fn ($query) => $query->where('city_id', $activeCityId))
            ->with('city')->orderBy(transColumn('name'))->get();
        $jobLevels = JobLevel::orderBy('grade')->get();
        $positions = Position::with(['center', 'program'])
            ->when($activeCityId, fn ($query) => $query->inCity($activeCityId))
            ->whereNot('id', $this->position->id)
            ->orderBy(transColumn('title'))->get();
        $departments = Department::orderby('name')->get();

        $title = $this->form->position->title;

        return view('livewire.position.form', compact('title', 'cities', 'programs', 'centers', 'jobLevels', 'departments', 'positions'));

    }

    public function save()
    {
        Gate::authorize('update', $this->position);
        $this->form->update();
        $this->successToast(__('general.success-message'));

        return redirect()->route('positions.index');
    }
}
