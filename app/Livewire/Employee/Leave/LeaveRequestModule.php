<?php

namespace App\Livewire\Employee\Leave;

use App\Livewire\Forms\LeaveRequestForm;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class LeaveRequestModule extends Component
{

    public LeaveBalance $leaveBalance;
    public LeaveRequest $leaveRequest;
    public LeaveRequestForm $form;
    public $showModal = false;
    public $modalTitle = 'Modal title';

    public function create()
    {
        $this->form->reset();
        $this->showModal = true;
        $this->modalTitle = __('general.add-new');
    }

    public function render()
    {
        $employee = auth()->user()->profile;
        $availableLeaves = auth()->user()->profile->availableLeaves();
        $allAvailableLeaves = $employee->allAvailableLeaves();
        $alternativeEmployees = Employee::where('city_id', $employee->city_id)->where('id', '!=', $employee->id)
            ->whereHas('contracts', function ($query) use ($employee) {
                $query->whereHas('position.department', function ($query) use ($employee) {
                    $query->where('id', $employee->department->id ?? null);
                });
            })->get();

        return view('livewire.employee.leave.index',compact('availableLeaves','allAvailableLeaves','alternativeEmployees'));
    }

    public function save(){
        if ($this->form->unit === 'day') {
            $this->form->start_date = Carbon::parse($this->form->start_date)->startOfDay();
            $this->form->end_date = Carbon::parse($this->form->end_date)->endOfDay();
        } elseif ($this->form->unit === 'hour') {
            $date = Carbon::parse($this->form->leave_date);
            $this->form->start_date = $date->copy()->setTimeFromTimeString($this->form->start_hour);
            $this->form->end_date = $date->copy()->setTimeFromTimeString($this->form->end_hour);
        }
        if (isset($this->form->id)) {
            $this->form->update();
        } else {

            $this->form->store();


        }

        $this->showModal = false;
        $this->form->reset();
        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();



    }
}
