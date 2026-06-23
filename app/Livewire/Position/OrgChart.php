<?php

namespace App\Livewire\Position;

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Livewire\Component;

class OrgChart extends Component
{
    /** @var Collection<int, City> */
    public $cities;

    /** @var Collection<int, Program> */
    public $programs;

    /** @var Collection<int, Center> */
    public $centers;

    /** @var Collection<int, Department> */
    public $departments;

    /** @var Collection<int, JobLevel> */
    public $jobLevels;

    /** Flat manager list for the "focus on a manager" filter. */
    public Collection $managers;

    /**
     * The built chart tree. Protected (not a Livewire public property) so the
     * Eloquent models — with their *filtered* eager-loaded team relations — are
     * never serialized/rehydrated between requests. Rehydration re-fetches models
     * fresh and drops the constrained eager loads, which would make collapsing /
     * zooming lazy-load every report unfiltered. Instead it is rebuilt once per
     * request in render().
     */
    protected array $tree = [];

    /** Whether the tree has already been built during the current request. */
    protected bool $treeBuilt = false;

    public array $collapsed = [];

    public array $stats = [
        'positions' => 0,
        'employees' => 0,
        'vacancies' => 0,
        'managers' => 0,
    ];

    // ---- Filters --------------------------------------------------------
    public ?int $city_id = null;

    public ?int $program_id = null;

    public ?int $center_id = null;

    public ?int $department_id = null;

    public ?int $job_level_id = null;

    /** Re-root the chart on a specific manager's subtree. */
    public ?int $focus_manager_id = null;

    /** Free-text search across employee name and position title. */
    public string $search = '';

    /** Active structural reporting line: "direct" (solid) or "tech" (dashed). */
    public string $manager_type = 'direct';

    // ---- View options ---------------------------------------------------
    /** Active page view: "chart" (tree) or "directory" (employee list). */
    public string $view = 'chart';

    /** "comfortable" or "compact" — compact maximises cards on screen. */
    public string $density = 'comfortable';

    public float $scale = 1.0;

    public int $managersCount = 0;

    /** Filter keys that, when changed, require the tree to be rebuilt. */
    private const TREE_FILTERS = [
        'city_id', 'program_id', 'center_id', 'department_id',
        'job_level_id', 'focus_manager_id', 'manager_type',
    ];

    /** The main "parent" city (Syria) that scopes the whole org. */
    private const MAIN_CITY_ID = 1;

    public function mount(): void
    {
        $this->managers = collect();
        $this->loadLookups();
        $this->refreshTree();
    }

    // -------------------------------------------------------------------
    // Lookups
    // -------------------------------------------------------------------

    private function loadLookups(): void
    {
        $this->cities = City::where('is_active', true)->orderBy(transColumn('name'))->get();
        $this->programs = Program::where('is_active', true)->orderBy('name')->get();
        $this->centers = Center::where('is_active', true)->orderBy('name')->get();
        $this->departments = Department::orderBy('name')->get();
        $this->jobLevels = JobLevel::orderBy('grade')->get();
        $this->refreshManagerOptions();
    }

    /** Managers available to focus on, scoped by the active reporting line. */
    private function refreshManagerOptions(): void
    {
        $this->managers = Position::query()
            ->visible()
            ->managersOnly($this->manager_type)
            ->whereHas('activeContracts')
            ->when($this->cityFilterIds(), fn (Builder $q, array $ids) => $q->whereIn('city_id', $ids))
            ->when($this->program_id, fn (Builder $q) => $q->where('program_id', $this->program_id))
            ->with(['activeContracts.employee', 'jobLevel'])
            ->orderByGrade()
            ->get();
    }

    // -------------------------------------------------------------------
    // Interaction
    // -------------------------------------------------------------------

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

    public function toggleDensity(): void
    {
        $this->density = $this->density === 'compact' ? 'comfortable' : 'compact';
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
        $this->reset([
            'city_id', 'program_id', 'center_id',
            'department_id', 'job_level_id', 'focus_manager_id', 'search',
        ]);
        $this->manager_type = 'direct';
        $this->loadLookups();
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

        if (in_array($property, ['city_id', 'program_id', 'manager_type'], true)) {
            // The focus list depends on city/program/line; reset a focus that no
            // longer appears so we never re-root on a hidden manager.
            $this->refreshManagerOptions();

            if ($this->focus_manager_id && ! $this->managers->contains('id', $this->focus_manager_id)) {
                $this->focus_manager_id = null;
            }
        }

        if (in_array($property, self::TREE_FILTERS, true)) {
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

    /** Whether a free-text search is currently active. */
    public function getIsSearchingProperty(): bool
    {
        return trim($this->search) !== '';
    }

    /**
     * Flat list of positions matching the search term (by position title or
     * employee name, in either locale), honouring the active lookup filters.
     * A flat grid is clearer than trying to prune the hierarchy.
     *
     * @return \Illuminate\Support\Collection<int, Position>
     */
    public function getSearchResultsProperty(): Collection
    {
        if (! $this->isSearching) {
            return collect();
        }

        $term = '%'.trim($this->search).'%';

        return Position::query()
            ->visible()
            ->where(function (Builder $query) use ($term) {
                $query->where('title_en', 'like', $term)
                    ->orWhere('title_ar', 'like', $term)
                    ->orWhereHas('activeContracts.employee', function (Builder $employee) use ($term) {
                        $employee->where('first_name_en', 'like', $term)
                            ->orWhere('last_name_en', 'like', $term)
                            ->orWhere('first_name_ar', 'like', $term)
                            ->orWhere('last_name_ar', 'like', $term);
                    });
            })
            ->when($this->cityFilterIds(), fn (Builder $query, array $ids) => $query->whereIn('city_id', $ids))
            ->when($this->program_id, fn (Builder $query) => $query->where('program_id', $this->program_id))
            ->when($this->center_id, fn (Builder $query) => $query->where('center_id', $this->center_id))
            ->when($this->department_id, fn (Builder $query) => $query->where('department_id', $this->department_id))
            ->when($this->job_level_id, fn (Builder $query) => $query->where('job_level_id', $this->job_level_id))
            ->with(Position::cardRelations($this->manager_type))
            ->orderByGrade()
            ->limit(60)
            ->get();
    }

    /** Count of filters currently narrowing the chart (drives the UI badge). */
    public function getActiveFilterCountProperty(): int
    {
        return collect([
            $this->city_id, $this->program_id, $this->center_id,
            $this->department_id, $this->job_level_id, $this->focus_manager_id,
        ])->filter()->count();
    }

    // -------------------------------------------------------------------
    // Tree building
    // -------------------------------------------------------------------

    private function refreshTree(): void
    {
        $this->tree = $this->buildTree();
        $this->treeBuilt = true;
        $this->collapsed = collect($this->tree['managers'])
            ->mapWithKeys(fn (Position $position) => [$position->id => $this->collapsed[$position->id] ?? true])
            ->all();
        $this->updateStats();
    }

    private function buildTree(): array
    {
        // The whole tree follows the active reporting line: switching to "tech"
        // re-roots on the tech-manager chain rather than grafting tech teams onto
        // the direct-manager skeleton.
        $managerColumn = $this->manager_type.'_manager_id';
        $teamRelation = $this->manager_type.'Team';
        $relations = Position::cardRelations($this->manager_type);

        $rootPositions = $this->resolveRoots($managerColumn, $relations);

        if ($rootPositions->isEmpty()) {
            $this->managersCount = 0;

            return ['root' => collect(), 'rootAssistant' => collect(), 'managers' => collect()];
        }

        $rootIds = $rootPositions->pluck('id');

        // When focusing on a specific manager there is no "assistant" tier — the
        // manager IS the root and their team becomes the first level.
        $rootAssistant = $this->focus_manager_id
            ? collect()
            : Position::query()
                ->visible()
                ->whereIn($managerColumn, $rootIds)
                ->whereHas('jobLevel', fn (Builder $query) => $query->where('grade', 2))
                ->with($relations)
                ->get();

        $teamLoader = function (Relation $query) use (&$teamLoader, $teamRelation, $relations): void {
            $this->applyFilters($query)
                ->orderByGrade()
                ->with([...$relations, $teamRelation => $teamLoader]);
        };

        // Top managers report directly to the root. They flow through the same
        // applyFilters() as the recursion so every tier obeys one rule; the root
        // always stays visible and the filters only prune the branches below it.
        $managers = Position::query()
            ->whereIn($managerColumn, $rootIds)
            ->whereNotIn('id', $rootAssistant->pluck('id'))
            ->tap(fn (Builder $query) => $this->applyFilters($query))
            ->orderByGrade()
            ->with([...$relations, $teamRelation => $teamLoader])
            ->get();

        $this->managersCount = $managers->count();

        return [
            'root' => $rootPositions,
            'rootAssistant' => $rootAssistant,
            'managers' => $managers,
        ];
    }

    private function resolveRoots(string $managerColumn, array $relations): Collection
    {
        // A focused manager becomes the single source of truth for the root.
        if ($this->focus_manager_id) {
            return Position::query()
                ->visible()
                ->whereKey($this->focus_manager_id)
                ->with($relations)
                ->get();
        }

        $roots = Position::query()
            ->visible()
            ->whereNull($managerColumn)
            ->whereHas('jobLevel', fn (Builder $query) => $query->where('grade', 1))
            ->with($relations)
            ->orderByGrade()
            ->orderBy('id')
            ->get();

        if ($roots->isNotEmpty()) {
            return $roots;
        }

        return Position::query()
            ->visible()
            ->whereNull($managerColumn)
            ->with($relations)
            ->orderByGrade()
            ->limit(1)
            ->get();
    }

    /**
     * City ids the chart should keep when a city filter is active. Filtering on
     * a branch city (e.g. Latakia) still keeps the main city (Syria) so the
     * HQ/parent positions stay visible and the tree remains connected.
     *
     * @return list<int>|null  Null when no city filter is active.
     */
    private function cityFilterIds(): ?array
    {
        if (! $this->city_id) {
            return null;
        }

        return array_values(array_unique([self::MAIN_CITY_ID, $this->city_id]));
    }

    private function applyFilters(Builder|Relation $query): Builder|Relation
    {
        return $query
            ->visible()
            ->whereNotNull($this->manager_type.'_manager_id')
            ->when($this->cityFilterIds(), fn (Builder $query, array $ids) => $query->whereIn('city_id', $ids))
            ->when($this->program_id, fn (Builder $query) => $query->where('program_id', $this->program_id))
            ->when($this->center_id, fn (Builder $query) => $query->where('center_id', $this->center_id))
            ->when($this->department_id, fn (Builder $query) => $query->where('department_id', $this->department_id))
            ->when($this->job_level_id, fn (Builder $query) => $query->where('job_level_id', $this->job_level_id));
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

        $walk($this->tree['rootAssistant']);
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
        // Actions that don't change a tree filter (collapse, zoom, density…) skip
        // refreshTree(); rebuild here so the view always renders a freshly filtered
        // tree rather than a rehydrated, unfiltered one.
        if (! $this->treeBuilt) {
            $this->refreshTree();
        }

        return view('livewire.position.org-chart', ['tree' => $this->tree]);
    }
}
