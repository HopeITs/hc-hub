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
        // Mapping of main donut sections → contract type IDs
        $groups = [
            'Annual' => [1, 2],
            'Trial Period' => [3, 4],
            'Service Agreement' => [5, 6],
        ];

        // Contract type names indexed by ID
        $types = ContractType::pluck('name_en', 'id');

        // All contracts loaded once
        $contracts = Contract::select('contract_type_id')->get();

        $this->chartData = collect($groups)->map(function ($typeIds, $mainCategory) use ($types, $contracts) {

            // count contracts per main category
            $total = $contracts->whereIn('contract_type_id', $typeIds)->count();

            // Build breakdown list
            $breakdown = collect($typeIds)->map(function ($typeId) use ($types, $contracts) {

                // Original DB label
                $label = $types[$typeId] ?? 'Unknown';

                // Shortened labels
                $label = match (strtolower($label)) {
                    'online agreement contract' => 'Online',
                    'onsite agreement contract' => 'Onsite',
                    default => $label,
                };

                return [
                    'category' => $label,
                    'value' => $contracts->where('contract_type_id', $typeId)->count(),
                ];
            });

            return [
                'category' => $mainCategory,
                'value' => $total,
                'breakdown' => $breakdown->values(),
            ];
        })->values();
    }

    public function render()
    {
        return view('livewire.dashboard.contract-type-employees-statistics', [
            'chartData' => $this->chartData,
        ]);
    }
}
