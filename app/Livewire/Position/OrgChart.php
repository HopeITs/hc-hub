<?php

namespace App\Livewire\Position;

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\Position;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Component;

class OrgChart extends Component
{
    public $cities;
    public $programs;
    public $centers;
    public $departments;

    public array $tree = [];
    public array $collapsed = [];
    public array $stats = [
        'positions' => 0,
        'employees' => 0,
        'vacancies' => 0,
        'managers' => 0,
    ];

    public ?int $city_id = null;
    public ?int $program_id = null;
    public ?int $center_id = null;
    public ?int $department_id = null;
    public string $manager_type = 'direct';
    public float $scale = 1.0;
    public int $managersCount = 0;

    public function mount(): void
    {
        $this->cities = City::where('is_active', true)->orderBy(transColumn('name'))->get();
        $this->programs = Program::where('is_active', true)->orderBy('name')->get();
        $this->centers = Center::where('is_active', true)->orderBy('name')->get();
        $this->departments = Department::orderBy('name')->get();
        $this->refreshTree();
    }

    public function toggleCollapse(int $id): void
    {
        $this->collapsed[$id] = ! ($this->collapsed[$id] ?? true);
    }

    public function expandAll(): void
    {
        foreach (array_keys($this->collapsed) as $id) {
            $this->collapsed[$id] = false;
        }

        $this->dispatch('expand-all');
    }

    public function collapseAll(): void
    {
        foreach (array_keys($this->collapsed) as $id) {
            $this->collapsed[$id] = true;
        }

        $this->dispatch('collapse-all');
    }

    public function zoomIn(): void
    {
        $this->scale = min(2, round($this->scale + 0.1, 1));
    }

    public function zoomOut(): void
    {
        $this->scale = max(0.3, round($this->scale - 0.1, 1));
    }

    public function fitToScreen(): void
    {
        $this->scale = max(0.3, min(1.0, 1.22 - ($this->managersCount * 0.05)));
        $this->dispatch('reset-pan');
    }

    public function resetZoom(): void
    {
        $this->scale = 1.0;
        $this->dispatch('reset-pan');
    }

    public function resetFilters(): void
    {
        $this->reset(['city_id', 'program_id', 'center_id', 'department_id']);
        $this->manager_type = 'direct';
        $this->programs = Program::where('is_active', true)->orderBy('name')->get();
        $this->centers = Center::where('is_active', true)->orderBy('name')->get();
        $this->refreshTree();
        $this->resetZoom();
    }

    public function updated(string $property): void
    {
        if ($property === 'city_id') {
            $this->updateCityOptions();
        }

        if ($property === 'program_id') {
            $this->updateProgramOptions();
        }

        if (in_array($property, ['city_id', 'program_id', 'center_id', 'department_id', 'manager_type'], true)) {
            $this->refreshTree();
            $this->fitToScreen();
        }
    }

    private function updateCityOptions(): void
    {
        if ($this->city_id === 1 || $this->city_id === null) {
            $this->centers = Center::where('is_active', true)->orderBy('name')->get();
            $this->programs = Program::where('is_active', true)->orderBy('name')->get();
        } else {
            $this->centers = Center::where('is_active', true)
                ->where('city_id', $this->city_id)
                ->orderBy('name')
                ->get();
            $this->programs = Program::where('is_active', true)
                ->whereHas('positions', fn (Builder $query) => $query->where('city_id', $this->city_id))
                ->orderBy('name')
                ->get();
        }

        $this->center_id = null;
        $this->program_id = null;
    }

    private function updateProgramOptions(): void
    {
        if ($this->program_id === 1 || $this->program_id === null) {
            $this->centers = Center::where('is_active', true)->orderBy('name')->get();
        } else {
            $this->centers = Center::where('is_active', true)
                ->whereHas('positions', fn (Builder $query) => $query->where('program_id', $this->program_id))
                ->orderBy('name')
                ->get();
        }

        $this->center_id = null;
    }

    private function refreshTree(): void
    {
        $this->tree = $this->buildTree();
        $this->collapsed = collect($this->tree['managers'])
            ->mapWithKeys(fn (Position $position) => [$position->id => $this->collapsed[$position->id] ?? true])
            ->all();
        $this->updateStats();
    }

    private function buildTree(): array
    {
        $root = Position::query()
            ->whereNull('direct_manager_id')
            ->with($this->positionRelations())
            ->get();

        $rootPosition = $root->first();
        if (! $rootPosition) {
            return ['root' => collect(), 'rootAssistant' => collect(), 'managers' => collect()];
        }

        $rootAssistant = Position::query()
            ->where('direct_manager_id', $rootPosition->id)
            ->whereHas('jobLevel', fn (Builder $query) => $query->where('grade', 2))
            ->with($this->positionRelations())
            ->get();

        $teamRelation = $this->manager_type.'Team';
        $teamLoader = function (Relation $query) use (&$teamLoader, $teamRelation): void {
            $this->applyFilters($query)
                ->orderBy('job_level_id')
                ->with([
                    ...$this->positionRelations(),
                    $teamRelation => $teamLoader,
                ]);
        };

        $managers = Position::query()
            ->where('direct_manager_id', $rootPosition->id)
            ->whereNotIn('id', $rootAssistant->pluck('id'))
            ->when($this->department_id, fn (Builder $query) => $query->where('department_id', $this->department_id))
            ->when($this->program_id, fn (Builder $query) => $query->where('program_id', $this->program_id))
            ->where('city_id', 1)
            ->orderBy('job_level_id')
            ->with([
                ...$this->positionRelations(),
                $teamRelation => $teamLoader,
            ])
            ->get();

        $this->managersCount = $managers->count();

        return [
            'root' => $root,
            'rootAssistant' => $rootAssistant,
            'managers' => $managers,
        ];
    }

    private function applyFilters(Builder|Relation $query): Builder|Relation
    {
        return $query
            ->whereNotNull($this->manager_type.'_manager_id')
            ->when($this->city_id, fn (Builder $query) => $query->where('city_id', $this->city_id))
            ->when($this->program_id, fn (Builder $query) => $query->where('program_id', $this->program_id))
            ->when($this->center_id, fn (Builder $query) => $query->where('center_id', $this->center_id))
            ->when($this->department_id, fn (Builder $query) => $query->where('department_id', $this->department_id));
    }

    private function positionRelations(): array
    {
        return [
            'activeContracts.employee.media',
            'city',
            'center',
            'department',
            'jobLevel',
        ];
    }

    private function updateStats(): void
    {
        $positions = collect($this->tree['root'])
            ->concat($this->tree['rootAssistant'])
            ->concat($this->tree['managers']);
        $teamRelation = $this->manager_type.'Team';

        $walk = function ($items) use (&$walk, &$positions, $teamRelation): void {
            foreach ($items as $position) {
                $children = $position->getRelation($teamRelation);
                $positions->push(...$children);
                $walk($children);
            }
        };

        $walk($this->tree['managers']);

        $positions = $positions->unique('id')->values();
        $employees = $positions->sum(fn (Position $position) => $position->activeContracts->count());
        $vacancies = $positions->sum(
            fn (Position $position) => max(0, $position->max_emp - $position->activeContracts->count())
        );

        $this->stats = [
            'positions' => $employees + $vacancies,
            'employees' => $employees,
            'vacancies' => $vacancies,
            'managers' => $this->managersCount,
        ];
    }

    public function render()
    {
        return view('livewire.position.org-chart');
    }
}
