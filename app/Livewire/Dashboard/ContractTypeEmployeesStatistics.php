<?php

namespace App\Livewire\Dashboard;

use App\Models\Contract;
use Illuminate\Support\Collection;
use Livewire\Component;

class ContractTypeEmployeesStatistics extends Component
{
    public Collection $statistics;

    public function mount(): void
    {
        $contracts = Contract::with('contractType')->where('is_active', true)->get();

        $grouped = $contracts->groupBy(fn($c) => $c->contractType?->name ?? 'Unknown');

        $this->statistics = $grouped
            ->map(function ($contracts, $typeName) {
                $breakdown = collect([
                    'Full Time' => $contracts->where('weekly_hours', '>', 30),
                    'Part Time' => $contracts->where('weekly_hours', '<=', 30),
                ])
                    ->map(function ($group, $label) use ($typeName) {
                        return [
                            'category' => "$label - $typeName",
                            'value' => $group->count(),
                        ];
                    })
                    ->values();

                return [
                    'category' => $typeName,
                    'value' => $contracts->count(),
                    'breakdown' => $breakdown,
                ];
            })
            ->values();
    }

    public function render()
    {
        return view('livewire.dashboard.contract-type-employees-statistics');
    }
}
