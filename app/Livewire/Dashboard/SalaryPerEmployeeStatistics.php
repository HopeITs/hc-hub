<?php

namespace App\Livewire\Dashboard;

use Livewire\Component;
use App\Models\Employee;

class SalaryPerEmployeeStatistics extends Component
{
    public array $series = [];
    public int $totalEmployees = 0;
    public int $highlightPercentage = 0;

    public function mount()
    {
        $buckets = [
            'per_month'   => 0,
            'per_session' => 0,
            'per_hour'    => 0,
        ];

        $employees = Employee::with(['contracts' => fn($q) => $q->orderByDesc('start_date')])->get();

        foreach ($employees as $employee) {
            $contract = $employee->contracts->first();
            if (!$contract) continue;

            $type = $contract->salary_per?->value;

            $map = [
                1 => 'per_month',
                2 => 'per_hour',
                3 => 'per_session',
            ];

            if (isset($map[$type])) {
                $buckets[$map[$type]]++;
            }
        }

        $meta = [
            'per_month'   => ['label' => 'Per Month',   'color' => '#be1f24'],
            'per_session' => ['label' => 'Per Session', 'color' => '#4b4b4b'],
            'per_hour'    => ['label' => 'Per Hour',    'color' => '#cdcdcd'],
        ];

        $this->series = [];
        $this->totalEmployees = array_sum($buckets);

        foreach ($meta as $key => $info) {
            $this->series[] = [
                'label' => $info['label'],
                'color' => $info['color'],
                'value' => $buckets[$key],
            ];
        }

        $this->highlightPercentage =
            $this->totalEmployees > 0
                ? (int) round($buckets['per_month'] / $this->totalEmployees * 100)
                : 0;
    }

    public function render()
    {
        return view('livewire.dashboard.salary-per-employee-statistics');
    }
}
