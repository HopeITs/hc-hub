<?php

namespace App\Livewire\Dashboard;

use App\Models\Contract;
use App\Models\ContractType;
use Livewire\Component;

class ContractTypeEmployeesStatistics extends Component
{
    public $chartData;

    public function mount()
    {
        $groups = [
            'Annual' => [1, 2],
            'Trial Period' => [3, 4],
            'Service Agreement' => [5, 6],
        ];

        $types = ContractType::pluck('name_en', 'id');

        $contracts = Contract::select('id', 'contract_type_id')->get();

        $mapped = [];

        foreach ($groups as $mainCategory => $typeIds) {

            $filtered = $contracts->whereIn('contract_type_id', $typeIds);

            $breakdown = [];

            foreach ($typeIds as $typeId) {
                $breakdown[] = [
                    'category' => $types[$typeId] ?? "Unknown",
                    'value' => $contracts->where('contract_type_id', $typeId)->count(),
                ];
            }

            $mapped[] = [
                'category' => $mainCategory,
                'value' => $filtered->count(),
                'breakdown' => $breakdown,
            ];
        }

        $this->chartData = $mapped;
    }

    public function render()
    {
        return view('livewire.dashboard.contract-type-employees-statistics', [
            'chartData' => $this->chartData,
        ]);
    }
}
