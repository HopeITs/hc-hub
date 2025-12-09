<?php

namespace App\Livewire\Dashboard;

use App\Enums\LeaveTypeCases;
use App\Models\Contract;
use App\Models\Employee;
use Livewire\Component;
use Carbon\Carbon;

class NoLeavesEmployeesStatistics extends Component
{
    public $noLeavesEmployees = [];

    public function mount()
    {
        $this->noLeavesEmployees = Employee::whereHas('contracts', function ($contractQuery) {
            $contractQuery->whereHas('leaveBalances', function ($balanceQuery) {
                $balanceQuery->where('type_id', LeaveTypeCases::Annual)
                    ->whereDoesntHave('leaveRequests');
            });
        })->get();
    }
    public function render()
    {



        return view('livewire.dashboard.no-leaves-employees-statistics');
    }
}
