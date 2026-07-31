<?php

namespace App\Livewire\Statistics;

use App\Livewire\Concerns\ScopesToAuthorizedCity;
use App\Models\Center;
use App\Models\City;
use App\Models\Contract;
use App\Models\Position;
use App\Models\Program;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ContractStatistics extends Component
{
    use ScopesToAuthorizedCity;

    public ?int $selectedCity = null;

    public ?int $selectedProgram = null;

    public $cities = [];

    public $programs = [];

    public $cards = [];

    public string $cardType = 'program';

    public function mount(): void
    {
        $cityId = $this->authorizedCityId();

        $this->cities = City::where('is_active', true)
            ->when($cityId !== null, fn ($q) => $q->whereKey($cityId))
            ->get();

        $this->programs = Program::where('is_active', true)
            ->orderby('name')
            ->get();

        $this->enforceCityScope();
    }

    public function loadData(): void
    {
        $this->enforceCityScope();

        $this->cards = [];

        if (! $this->selectedCity && ! $this->selectedProgram) {
            $this->cardType = 'program';
            $programs = Program::where('is_active', true)
                ->get();

            foreach ($programs as $program) {
                $positionsQuery = Position::where('program_id', $program->id);

                $contractsQuery = Contract::where('is_active', true)
                    ->whereHas('position', fn ($q) => $q->where('program_id', $program->id));

                $this->cards[] = [
                    'name' => $program->slug,
                    'centers' => (clone $positionsQuery)->whereNotNull('center_id')->distinct('center_id')->count(),
                    'color' => 'bg-primary',
                    'program_slug' => $program->slug,
                ] + $this->getCardBreakdown($contractsQuery);
            }

        } elseif (! $this->selectedCity && $this->selectedProgram) {
            $this->cardType = 'center';
            $centers = Center::where('is_active', true)
                ->whereHas('positions', fn ($q) => $q->where('program_id', $this->selectedProgram))
                ->get();

            foreach ($centers as $center) {
                $contractsQuery = Contract::where('is_active', true)
                    ->whereHas('position', fn ($q) => $q
                        ->where('center_id', $center->id)
                        ->where('program_id', $this->selectedProgram),
                    );

                $this->cards[] = [
                    'name' => $center->name,
                    'centers' => null,
                    'color' => 'bg-green-500',
                    'center_id' => $center->id,
                ] + $this->getCardBreakdown($contractsQuery);
            }

        } elseif ($this->selectedCity && ! $this->selectedProgram) {
            $this->cardType = 'program';
            $programs = Program::where('is_active', true)
                ->whereHas('positions', fn ($q) => $q->where('city_id', $this->selectedCity))
                ->get();

            foreach ($programs as $program) {
                $positionsQuery = Position::where('program_id', $program->id)
                    ->where('city_id', $this->selectedCity);

                $contractsQuery = Contract::where('is_active', true)
                    ->whereHas('position', fn ($q) => $q
                        ->where('program_id', $program->id)
                        ->where('city_id', $this->selectedCity),
                    );

                $this->cards[] = [
                    'name' => $program->name,
                    'centers' => (clone $positionsQuery)->whereNotNull('center_id')->distinct('center_id')->count(),
                    'color' => 'bg-primary',
                    'program_slug' => $program->slug,
                ] + $this->getCardBreakdown($contractsQuery);
            }

        } elseif ($this->selectedCity && $this->selectedProgram) {
            $this->cardType = 'center';
            $centers = Center::where('is_active', true)
                ->where('city_id', $this->selectedCity)
                ->whereHas('positions', fn ($q) => $q->where('program_id', $this->selectedProgram))
                ->get();

            foreach ($centers as $center) {
                $contractsQuery = Contract::where('is_active', true)
                    ->whereHas('position', fn ($q) => $q
                        ->where('center_id', $center->id)
                        ->where('program_id', $this->selectedProgram)
                        ->where('city_id', $this->selectedCity),
                    );

                $this->cards[] = [
                    'name' => $center->name,
                    'centers' => null,
                    'color' => 'bg-green-500',
                    'center_id' => $center->id,
                ] + $this->getCardBreakdown($contractsQuery);
            }
        }
    }

    public function resetFilters(): void
    {
        $this->selectedCity = null;
        $this->selectedProgram = null;

        // "Clear filters" must not become a way back to nationwide figures.
        $this->enforceCityScope();
    }

    public function render(): View
    {
        $this->loadData();

        //        dd($this->cards, $this->programs);
        return view('livewire.dashboard.contract-statistics', [
            'cards' => $this->cards,
            'cities' => $this->cities,
            'programs' => $this->programs,
            'cardType' => $this->cardType,
        ]);
    }

    /**
     * Pin the city filter for regionally scoped roles.
     *
     * selectedCity is a public property, so it is client-writable, and null
     * meant "every city" — the branch a regional user landed on by default.
     * That produced nationwide contract, employee, and salary totals for a
     * user entitled to one city. Re-applied at the top of loadData() so a
     * tampered value is overwritten before any aggregate is computed, not just
     * at mount time.
     */
    private function enforceCityScope(): void
    {
        $cityId = $this->authorizedCityId();

        if ($cityId !== null) {
            $this->selectedCity = $cityId;
        }
    }

    private function countByTypeTokens(Builder $query, array $tokens): int
    {
        return (clone $query)
            ->whereHas('contractType', function ($q) use ($tokens) {
                foreach ($tokens as $t) {
                    $q->where(transColumn('name'), 'like', "%{$t}%");
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
}
