<?php

namespace App\Livewire\Employee\Leave;

use App\Livewire\Forms\LeaveRequestForm;
use Livewire\Component;

class UpdateLeaveRequest extends Component
{
    public LeaveRequestForm $form;

    public $title = '';

    public function mount()
    {
        $this->title = __('general.update');
    }

    public function render()
    {
        return view('livewire.employee.leave.form');
    }
}
