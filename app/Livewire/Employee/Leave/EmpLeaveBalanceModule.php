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
        $this->form->updateAnnualLeaves();


        $this->showModal = false;
        $this->form->reset();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();


    }

    protected function confirmationMessage(): string
    {

        $name = $this->form->balance->contract->employee->name;
        $operation = __('general.'.$this->form->operation);
        $amount = $this->form->amount . ' ' . trans_choice('general.'.$this->form->unit , $this->form->amount) ;
        $add_badge = $this->form->add_badge ? __('general.yes') :  __('general.no');
        $notes = $add_badge ? $this->form->notes : '';


        return Blade::render(
            '<x-alerts.leave-balance-confirmation
                :name="$name"
                :operation="$operation"
                :amount="$amount"
                :add_badge="$add_badge"
                :notes="$notes"

            />',compact('name', 'operation', 'amount', 'add_badge', 'notes')
        );
    }


}
