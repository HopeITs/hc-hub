<?php

namespace App\Livewire\Dashboard;

use App\Models\Contract;
use App\Models\ContractType;
use App\Support\StatisticsCache;
use Livewire\Component;

class ContractTypeEmployeesStatistics extends Component
{
    /**
     * Donut sections → the contract_type_id values that roll up into them.
     *
     * NOTE: these ids are hard-coded to match ContractTypeSeeder. They were
     * already hard-coded before; keeping them here, named, at least makes the
     * coupling visible rather than burying it mid-method.
     */
    private const GROUPS = [
        'Annual' => [1, 2],
        'Trial Period' => [3, 4],
        'Service Agreement' => [5, 6],
    ];

    public $chartData;

    public function mount()
    {
        /**
         * Contract counts per type, rolled up into the three donut sections.
         *
         * Was: `Contract::select('contract_type_id')->get()` — every contract row
         * pulled into a collection — followed by seven separate in-memory ->where()
         * passes over it (one per group plus one per breakdown entry). Now a single
         * GROUP BY returns the per-type totals and the rollup is done over an array
         * of at most six integers.
         *
         * Counts are per CONTRACT, not per employee, which is what this card has
         * always shown — deliberately left as-is.
         */
        [$countsByType, $typeNames] = StatisticsCache::remember('contracts.by-type', fn () => [
            Contract::query()
                ->groupBy('contract_type_id')
                ->selectRaw('contract_type_id, COUNT(*) as total')
                ->pluck('total', 'contract_type_id')
                ->all(),
            ContractType::pluck('name_en', 'id')->all(),
        ]);

        $this->chartData = collect(self::GROUPS)
            ->map(fn (array $typeIds, string $mainCategory) => [
                'category' => $mainCategory,
                'value' => array_sum(array_map(
                    fn ($typeId) => (int) ($countsByType[$typeId] ?? 0),
                    $typeIds,
                )),
                'breakdown' => collect($typeIds)
                    ->map(fn ($typeId) => [
                        'category' => $this->shortLabel($typeNames[$typeId] ?? 'Unknown'),
                        'value' => (int) ($countsByType[$typeId] ?? 0),
                    ])
                    ->values(),
            ])
            ->values();
    }

    public function render()
    {
        return view('livewire.dashboard.contract-type-employees-statistics', [
            'chartData' => $this->chartData,
        ]);
    }

    /**
     * The seeded names are too long to fit a donut legend, so the two agreement
     * variants are abbreviated. Anything else is shown verbatim.
     */
    private function shortLabel(string $name): string
    {
        return match (strtolower($name)) {
            'online agreement contract' => 'Online',
            'onsite agreement contract' => 'Onsite',
            default => $name,
        };
    }
}
