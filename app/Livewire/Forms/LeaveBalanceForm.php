<?php

namespace App\Livewire\Forms;

use App\Models\Contract;
use App\Enums\SalaryPer;
use App\Models\LeaveBalance;
use App\Models\Position;
use Illuminate\Validation\Rule;
use Livewire\Form;

class LeaveBalanceForm extends Form
{
    public ?LeaveBalance $balance = null;

    public $contract_id;
    public $type_id;
    public $deserved;
    public $used;


    protected function rules()
    {
        return [
            'contract_id' => 'required|exists:contracts,id',
            'type_id' => 'required|exists:contract_types,id',
            'deserved' => 'required|integer',
            'used' => 'required|integer:'


        ];
    }

    public function setPosition(LeaveBalance $balance): void
    {
        $this->balance = $balance;


    }

    public function store()
    {
        $this->validate();
        $this->balance = LeaveBalance::create($this->pull());
        $this->reset();

    }

    public function update()
    {
        $this->validate();

        $this->position->update(
            $this->all()
        );

        $this->reset();
    }


}
