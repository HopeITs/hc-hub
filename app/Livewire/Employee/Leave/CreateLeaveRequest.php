<?php

namespace App\Livewire\Employee\Leave;


use App\Livewire\Forms\LeaveRequestForm;
use App\Models\Employee;
use App\Traits\WithAlerts;
use Carbon\Carbon;
use Livewire\Component;

class CreateLeaveRequest extends Component
{
    use WithAlerts;

    public LeaveRequestForm $form;
    public $title = '';

    public function mount()
    {
        $this->title = __('general.add-new');
    }

    public function render()
    {
        $employee = auth()->user()->profile;
        $availableLeaves = $employee->allAvailableLeaves();
        $alternativeEmployees = Employee::where('city_id', $employee->city_id)->where('id', '!=', $employee->id)
            ->whereHas('contracts', function ($query) use ($employee) {
                $query->whereHas('position.department', function ($query) use ($employee) {
                    $query->where('id', $employee->department->id);
                });
            })->get();

        return view('livewire.employee.leave.form', compact('availableLeaves', 'alternativeEmployees'));
    }

    public function save()
    {

        if ($this->form->unit === 'day') {
            $this->form->start_date = Carbon::parse($this->form->start_date)->startOfDay();
            $this->form->end_date = Carbon::parse($this->form->end_date)->endOfDay();
        } elseif ($this->form->unit === 'hour') {
            $date = Carbon::parse($this->form->leave_date);
            $this->form->start_date = $date->copy()->setTimeFromTimeString($this->form->start_hour);
            $this->form->end_date = $date->copy()->setTimeFromTimeString($this->form->end_hour);
        }

        $this->form->store();
        $this->successToast();

    }
}
