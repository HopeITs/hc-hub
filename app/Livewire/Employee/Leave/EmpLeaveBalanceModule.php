<?php

namespace App\Livewire\Employee\Leave;

use App\Enums\LeaveRequestStatus;
use App\Livewire\Forms\LeaveBalanceForm;
use App\Livewire\Forms\LeaveRequestForm;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class EmpLeaveBalanceModule extends Component
{

    public LeaveBalance $leaveBalance;

    public LeaveBalanceForm $form;
    public $showModal = false;
    public $modalTitle = 'Modal title';
    protected $listeners = [
        'openEdit' => 'edit',

        'processSave' => 'processSave'
    ];


    public function edit(LeaveBalance $id)
    {
        $this->form->setLeaveBalance($id);

        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }


    public function render()
    {

        return view('livewire.employee.leave.balance');
    }


    public function confirmSave()
    {
        $html = $this->confirmationMessage();

        LivewireAlert::title(__('general.confirmation'))
            ->timer(null)
            ->question()
            ->html($html)
            ->withConfirmButton(__('general.save'))
            ->onConfirm('processSave')
            ->withCancelButton(__('general.cancel'))->show();

    }

    public function processSave()
    {
        if ($this->form->id) {
            $this->form->update();
        } else {
            $this->form->store();
        }

        $this->formModal = false;
        $this->form->reset();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        $this->refreshCounts();
    }

    protected function confirmationMessage(): string
    {
        $leaveTypeName = LeaveBalance::find($this->form->leave_balance_id)
            ->leaveType
            ->name ?? 'N/A';

        $type = $this->form->type;

        $start = $this->resolveStart();
        $end = $this->resolveEnd();
        $duration = $this->resolveDuration();
        $date = $this->form->leave_date;
        $reason = $this->form->reason;

        return Blade::render(
            '<x-alerts.leave-request-confirmation
                :type="$type"
                :leave_type="$leaveTypeName"
                :start="$start"
                :end="$end"
                :duration="$duration"
                :reason="$reason"
                :date="$date"
            />',
            compact('type', 'leaveTypeName', 'start', 'end', 'duration', 'reason', 'date')
        );
    }


}
