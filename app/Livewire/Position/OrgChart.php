<?php
// app/Livewire/OrgChart.php
namespace App\Livewire\Position;

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\Position;
use App\Models\Program;
use App\Traits\SortableTable;
use Illuminate\Support\Collection;
use Livewire\Component;

class OrgChart extends Component
{
    public $cities;
    public $programs;
    public $centers;
    public $departments;
    public array $tree = [];
    public ?int $city_id = null;
    public ?int $program_id = null;
    public ?int $center_id = null;
    public ?int $department_id = null;
    public ?string $manager_type = 'direct'; // 'direct', 'tech', or null

    public function mount(): void
    {
        $this->cities = City::where('is_active', true)->get();
        $this->programs = Program::where('is_active', true)->get();
        $this->centers = Center::where('is_active', true)->get();
        $this->departments = Department::all();
        $this->tree = $this->buildTree();
    }

    public function updated($property): void
    {

        if($property === 'city_id'){
            if($this->city_id === 1 || $this->city_id === null){
                $this->centers = Center::where('is_active', true)->get();
                $this->programs = Program::where('is_active', true)->get();
            }else{
                $this->centers = Center::where('is_active', true)->where('city_id',$this->city_id)->get();
                $this->programs = Program::where('is_active', true)->whereHas('positions', function ($query) {
                    $query->where('city_id', $this->city_id);
                })->get();

            }
        }
        if($property === 'program_id'){
            if($this->program_id === 1 || $this->program_id === null){
                $this->centers = Center::where('is_active', true)->get();
            }else{
                $this->centers = Center::where('is_active', true)->whereHas('positions', function ($query) {
                    $query->where('program_id', $this->program_id);
                })->get();

            }
        }


        if (in_array($property, ['city_id', 'program_id', 'center_id', 'department_id', 'manager_type'])) {
            $this->tree = $this->buildTree();
        }
    }

    private function buildTree(): array
    {
        $query = Position::with(['jobLevel', 'directTeam.jobLevel', 'directTeam.directTeam'])
            ->where('city_id', 1)
            ->when($this->city_id, fn($query) => $query->orwhere('city_id', $this->city_id))
            ->when($this->program_id, fn($query) => $query->where('program_id', $this->program_id))
            ->when($this->center_id, fn($query) => $query->where('center_id', $this->center_id))
            ->when($this->department_id, fn($query) => $query->where('department_id', $this->department_id));
//dd($query->get());

        if ($this->manager_type === 'direct') {
            $query->orderBy('direct_manager_id');
        } elseif ($this->manager_type === 'tech') {
            $query->orderBy('tech_manager_id');
        }
        $positions = $query->get();
        $root = $positions->where('direct_manager_id', null);
        if (!$root) return [];

        // Level 1 — board
        $rootAssistant = $positions->filter(fn($p) => $p->direct_manager_id === $root[0]->id &&
            $p->jobLevel?->grade == 2
        );

        // Level 2 — managers
        if ($this->manager_type === 'direct') {
            $managers = $positions->filter(fn($p) => $p->direct_manager_id === $root[0]->id &&
                $p->city_id === 1 &&
                in_array($p->jobLevel?->grade, [1, 3, 5]) //program manager ||project manager || department manager
            );
        } else {
            $techManagerIds = $positions
                ->pluck('tech_manager_id')
                ->filter()
                ->unique()
                ->values();

            $managers = $positions->whereIn('id', $techManagerIds);


        }


        // Teams


        return [
            'root' => $root,
            'board' => $rootAssistant->values(),
            'managers' => $managers->values(),

        ];
    }

    public function render()
    {

        return view('livewire.position.org-chart', [
            'tree' => $this->buildTree(),
        ]);
    }
}
