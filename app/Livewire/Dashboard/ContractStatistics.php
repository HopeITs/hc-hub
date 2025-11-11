<?php

namespace App\Livewire\Dashboard;

use App\Models\City;
use App\Models\Program;
use App\Models\Contract;
use App\Models\Position;
use Livewire\Component;
use Illuminate\Contracts\View\View;

class ContractStatistics extends Component
{
    public ?int $selectedCity = null;
    public ?int $selectedProgram = null;

    public $cities = [];
    public $programs = [];

    public function mount(): void
    {
        $this->cities = City::where('is_active', true)
            ->orderBy('name_en')
            ->get(['id', 'name_en']);

        $this->programs = Program::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function contractsBaseQuery()
    {
        return Contract::query()
            ->where('is_active', true)
            ->with(['contractType', 'position.program', 'position.city'])
            ->when($this->selectedCity, function ($q) {
                $q->whereHas('position.city', fn($qq) => $qq->where('id', $this->selectedCity));
            });
    }

    private function programContractsQuery(int $programId)
    {
        return (clone $this->contractsBaseQuery())->whereHas('position.program', fn($q) => $q->where('id', $programId));
    }

    private function countByTypeLike($query, string $contains): int
    {
        return (clone $query)->whereHas('contractType', fn($q) => $q->where('name_en', 'like', "%{$contains}%"))->count();
    }

    private function countByTypeTokens($query, array $tokens): int
    {
        return (clone $query)
            ->whereHas('contractType', function ($q) use ($tokens) {
                foreach ($tokens as $t) {
                    $q->where('name_en', 'like', "%{$t}%");
                }
            })
            ->count();
    }

    private function centersCountForProgram(int $programId): int
    {
        return Position::query()->where('program_id', $programId)->when($this->selectedCity, fn($q) => $q->where('city_id', $this->selectedCity))->whereNotNull('center_id')->distinct('center_id')->count('center_id');
    }

    public function getProgramCardsProperty(): array
    {
        $programs = $this->programs->when($this->selectedProgram, function ($col) {
            return $col->where('id', $this->selectedProgram);
        });

        $cards = [];

        foreach ($programs as $program) {
            $q = $this->programContractsQuery($program->id);

            $cards[] = [
                'program' => $program->name,
                'employees' => (clone $q)->distinct('employee_id')->count('employee_id'),
                'centers' => $this->centersCountForProgram($program->id),

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

                'color' => 'bg-primary-500',
            ];
        }

        return $cards;
    }

    public function applyFilters(): void {}

    public function resetFilters(): void
    {
        $this->selectedCity = null;
        $this->selectedProgram = null;
    }

    public function render(): View
    {
        return view('livewire.dashboard.contract-statistics', [
            'cards' => $this->programCards,
            'cities' => $this->cities,
            'programs' => $this->programs,
        ]);
    }
}
