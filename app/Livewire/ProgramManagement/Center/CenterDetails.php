<?php

namespace App\Livewire\ProgramManagement\Center;

use App\Models\Center;
use App\Models\ContractType;
use App\Models\Program;
use Livewire\Component;

class CenterDetails extends Component
{
    public Center $center;
    public string $activeTab = 'contracts';
    public array $contractData = [];
    public array $programData = [];

    public function mount(Center $center): void
    {
        $this->center = $center->load('city');
        $this->loadContractData();
        $this->loadProgramData();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Load employee counts by contract type for this center.
     */
    protected function loadContractData(): void
    {
        // fetch all active contracts in this center
        $contracts = $this->center->positions()
            ->with(['contracts' => function ($query) {
                $query->where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now());
            }, 'contracts.contractType'])
            ->get()
            ->pluck('contracts')
            ->flatten();

        // group by contract type
        $grouped = $contracts->groupBy('contract_type_id')
            ->map(fn($group) => $group->unique('employee_id')->count());

        // fetch labels for each contract type
        $contractTypes = ContractType::whereIn('id', $grouped->keys())
            ->get(['id', 'name_en', 'name_ar', 'type', 'place']);

        $labels = $contractTypes->mapWithKeys(function ($ct) {
            $name = $ct->name ?? $ct->name_en ?? $ct->name_ar ?? 'Unknown';
            return [$ct->id => strtolower($name)];
        });

        // initialize structure
        $data = [
            'annual' => [
                'Annual Full Time' => 0,
                'Annual Part Time' => 0,
            ],
            'service' => [
                'Online Service Agreement' => 0,
                'Onsite Service Agreement' => 0,
            ],
            'trial' => [
                'Trial Full Time' => 0,
                'Trial Part Time' => 0,
            ],
        ];

        // classify
        foreach ($grouped as $typeId => $count) {
            $label = $labels[$typeId] ?? '';
            if (str_contains($label, 'annual') && str_contains($label, 'full')) {
                $data['annual']['Annual Full Time'] += $count;
            } elseif (str_contains($label, 'annual') && str_contains($label, 'part')) {
                $data['annual']['Annual Part Time'] += $count;
            } elseif (str_contains($label, 'online')) {
                $data['service']['Online Service Agreement'] += $count;
            } elseif (str_contains($label, 'onsite')) {
                $data['service']['Onsite Service Agreement'] += $count;
            } elseif (str_contains($label, 'trial') && str_contains($label, 'full')) {
                $data['trial']['Trial Full Time'] += $count;
            } elseif (str_contains($label, 'trial') && str_contains($label, 'part')) {
                $data['trial']['Trial Part Time'] += $count;
            }
        }

        $this->contractData = $data;
    }

    /**
     * Load employee counts by program for this center.
     */
    protected function loadProgramData(): void
    {
        $positions = $this->center->positions()
            ->with(['contracts' => function ($query) {
                $query->where('is_active', true)
                    ->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now());
            }, 'program'])
            ->get();

        $programCounts = [];

        foreach ($positions as $position) {
            $programName = $position->program?->name ?? $position->program?->name_en ?? $position->program?->name_ar ?? 'Unknown Program';
            $activeContracts = $position->contracts->unique('employee_id')->count();
            $programCounts[$programName] = ($programCounts[$programName] ?? 0) + $activeContracts;
        }

        $this->programData = collect($programCounts)
            ->map(fn($count, $program) => ['program' => $program, 'count' => $count])
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.program-management.center-details');
    }
}
