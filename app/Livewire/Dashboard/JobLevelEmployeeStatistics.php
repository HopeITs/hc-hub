<?php

namespace App\Livewire\Dashboard;

use App\Models\Employee;
use Livewire\Component;

class JobLevelEmployeeStatistics extends Component
{
    public $jobLevelLabels = [];

    public $employeeCounts = [];

    public function mount()
    {
        $employeeData = Employee::query()
            ->with(['contracts' => function ($query) {
                $query->where('is_active', true)
                    ->orderByDesc('start_date')
                    ->limit(1);
            }, 'contracts.position.jobLevel'])
            ->get();

        $jobLevelCounts = $employeeData
            ->map(function ($employee) {
                $contract = $employee->contracts->first();

                return optional($contract?->position?->jobLevel)->name;
            })
            ->filter()
            ->countBy()
            ->sortKeys()
            ->toArray();

        $this->jobLevelLabels = array_keys($jobLevelCounts);
        $this->employeeCounts = array_values($jobLevelCounts);
    }

    public function render()
    {
        return view('livewire.dashboard.job-level-employee-statistics');
    }
}
