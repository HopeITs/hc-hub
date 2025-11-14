<?php

namespace App\Livewire\Dashboard;

use App\Models\City;
use App\Models\Program;
use App\Models\Contract;
use App\Models\Center;
use App\Models\Position;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ContractStatistics extends Component
{
    public ?int $selectedCity = null;
    public ?int $selectedProgram = null;

    public $cities = [];
    public $programs = [];
    public $cards = [];
    public string $cardType = 'program';

    private function getTranslatableColumn(string $baseColumn): string
    {
        return app()->getLocale() === 'ar' ? "{$baseColumn}_ar" : "{$baseColumn}_en";
    }

    public function mount(): void
    {
        $this->cities = City::where('is_active', true)
            ->orderBy($this->getTranslatableColumn('name'))
            ->get();

        $this->programs = Program::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function countByTypeTokens(Builder $query, array $tokens): int
    {
        return (clone $query)
            ->whereHas('contractType', function ($q) use ($tokens) {
                foreach ($tokens as $t) {
                    $q->where('name_en', 'like', "%{$t}%");
                }
            })
            ->count();
    }

    private function getCardBreakdown(Builder $contractsQuery): array
    {
        $q = clone $contractsQuery;

        return [
            'employees' => (clone $q)->distinct('employee_id')->count('employee_id'),

            'annual' => [
                'full' => $this->countByTypeTokens($q, ['Annual', 'Full']),
                'part' => $this->countByTypeTokens($q, ['Annual', 'Partial']),
            ],
            'service' => [
                'online' => $this->countByTypeTokens($q, ['Agreement', 'Online']),
                'onsite' => $this->countByTypeTokens($q, ['Agreement', 'Onsite']),
            ],
            'trial' => [
                'full' => $this->countByTypeTokens($q, ['Trial', 'Full']),
                'part' => $this->countByTypeTokens($q, ['Trial', 'Partial']),
            ],
        ];
    }

    public function loadData(): void
    {
        $this->cards = [];

        if (!$this->selectedCity && !$this->selectedProgram) {
            // Scenario 1: All Cities + All Programs -> Show PROGRAM cards
            $this->cardType = 'program';
            $programs = Program::where('is_active', true)->orderBy('name')->get();

            foreach ($programs as $program) {
                $positionsQuery = Position::where('program_id', $program->id);

                $contractsQuery = Contract::where('is_active', true)
                    ->whereHas('position', fn($q) => $q->where('program_id', $program->id));

                $this->cards[] = [
                    'name' => $program->name,
                    'centers' => (clone $positionsQuery)->whereNotNull('center_id')->distinct('center_id')->count(),
                    'color' => 'bg-primary-500',
                ] + $this->getCardBreakdown($contractsQuery);
            }

        } elseif (!$this->selectedCity && $this->selectedProgram) {
            // Scenario 2: All Cities + 1 Program -> Show CENTER cards
            $this->cardType = 'center';
            $centers = Center::where('is_active', true)
                ->whereHas('positions', fn($q) => $q->where('program_id', $this->selectedProgram))
                ->orderBy('name')
                ->get();

            foreach ($centers as $center) {
                $contractsQuery = Contract::where('is_active', true)
                    ->whereHas('position', fn($q) => $q
                        ->where('center_id', $center->id)
                        ->where('program_id', $this->selectedProgram)
                    );

                $this->cards[] = [
                    'name' => $center->name,
                    'centers' => null,
                    'color' => 'bg-green-500',
                ] + $this->getCardBreakdown($contractsQuery);
            }

        } elseif ($this->selectedCity && !$this->selectedProgram) {
            // Scenario 3: 1 City + All Programs -> Show PROGRAM cards
            $this->cardType = 'program';
            $programs = Program::where('is_active', true)
                ->whereHas('positions', fn($q) => $q->where('city_id', $this->selectedCity))
                ->orderBy('name')
                ->get();

            foreach ($programs as $program) {
                $positionsQuery = Position::where('program_id', $program->id)
                                          ->where('city_id', $this->selectedCity);

                $contractsQuery = Contract::where('is_active', true)
                    ->whereHas('position', fn($q) => $q
                        ->where('program_id', $program->id)
                        ->where('city_id', $this->selectedCity)
                    );

                $this->cards[] = [
                    'name' => $program->name,
                    'centers' => (clone $positionsQuery)->whereNotNull('center_id')->distinct('center_id')->count(),
                    'color' => 'bg-primary-500',
                ] + $this->getCardBreakdown($contractsQuery);
            }

        } elseif ($this->selectedCity && $this->selectedProgram) {
            // Scenario 4: 1 City + 1 Program -> Show CENTER cards
            $this->cardType = 'center';
            $centers = Center::where('is_active', true)
                ->where('city_id', $this->selectedCity)
                ->whereHas('positions', fn($q) => $q->where('program_id', $this->selectedProgram))
                ->orderBy('name')
                ->get();

            foreach ($centers as $center) {
                $contractsQuery = Contract::where('is_active', true)
                    ->whereHas('position', fn($q) => $q
                        ->where('center_id', $center->id)
                        ->where('program_id', $this->selectedProgram)
                        ->where('city_id', $this->selectedCity)
                    );

                $this->cards[] = [
                    'name' => $center->name,
                    'centers' => null,
                    'color' => 'bg-green-500',
                ] + $this->getCardBreakdown($contractsQuery);
            }
        }
    }


    public function applyFilters(): void {}

    public function resetFilters(): void
    {
        $this->selectedCity = null;
        $this->selectedProgram = null;
    }

    public function render(): View
    {
        $this->loadData();

        return view('livewire.dashboard.contract-statistics', [
            'cards' => $this->cards,
            'cities' => $this->cities,
            'programs' => $this->programs,
            'cardType' => $this->cardType,
        ]);
    }
}
