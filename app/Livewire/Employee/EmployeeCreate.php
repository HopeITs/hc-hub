<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use App\Livewire\Employee\Validators\EmployeeFormValidator;

class EmployeeCreate extends Component
{
    public $item = [];

    public function mount()
    {
        $this->item = [
            'name' => [],
            'father_name' => [],
            'mother_name' => [],
            'address' => [],
            'years_of_service' => 0,
            'gender' => 0,
            'marital_status' => 0,
            'children' => 0,
            'nationality' => 'syrian',
            'is_active' => true,
            'is_board_member' => false,
        ];
    }

    protected function rules()
    {
        return EmployeeFormValidator::rules();
    }

    public function save()
    {
        $this->validate();
        Employee::create($this->item);
        LivewireAlert::title(__('general.success-message'))->success()->show();
        return redirect()->route('employees.index');
    }

    public function render()
    {
        return view('livewire.employee.employee-create', [
            'allEmployees' => Employee::select('id', 'name')->get(),
        ]);
    }
}
