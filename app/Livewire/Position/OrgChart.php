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
    public  $scale = 1.0;
    public int $managersCount = 10;
    public ?string $manager_type = 'tech'; // 'direct', 'tech', or null

    public function mount(): void
    {
        $this->cities = City::where('is_active', true)->get();
        $this->programs = Program::where('is_active', true)->get();
        $this->centers = Center::where('is_active', true)->get();
        $this->departments = Department::all();
        $this->tree = $this->buildTree();

    }

    public function zoomIn()
    {

        if ($this->scale < 2) { // حد أعلى
            $this->scale += 0.1;
        }
    }

    public function zoomOut()
    {
        if ($this->scale > 0.3) {
            $this->scale -= 0.1;
        }
    }

    public function fitToScreen()
    {
        $count = $this->managersCount;
        $scale = 1.22 - ($count * 0.05);
        $this->scale = max(0.3, min(1.0, $scale));
        $this->dispatch('reset-pan');
    }

    public function resetZoom()
    {
        $this->scale = 1;
    }

    public function updated($property): void
    {

        if ($property === 'city_id') {
            if ($this->city_id === 1 || $this->city_id === null) {
                $this->centers = Center::where('is_active', true)->get();
                $this->programs = Program::where('is_active', true)->get();
            } else {
                $this->centers = Center::where('is_active', true)->where('city_id', $this->city_id)->get();
                $this->programs = Program::where('is_active', true)->whereHas('positions', function ($query) {
                    $query->where('city_id', $this->city_id);
                })->get();

            }
        }
        if ($property === 'program_id') {
            if ($this->program_id === 1 || $this->program_id === null) {
                $this->centers = Center::where('is_active', true)->get();
            } else {
                $this->centers = Center::where('is_active', true)->whereHas('positions', function ($query) {
                    $query->where('program_id', $this->program_id);
                })->get();

            }
        }


        if (in_array($property, ['city_id', 'program_id', 'center_id', 'department_id', 'manager_type'])) {
            $this->tree = $this->buildTree();
            $this->fitToScreen();
        }
    }

    private function buildTree(): array
    {

        $root = Position::where('direct_manager_id', null)->get();


        // Level 1 — board
        $rootAssistant = Position::where('direct_manager_id', $root[0]->id)
            ->whereHas('jobLevel', function ($query) {
                $query->where('grade', 2);
            })->get();

        $managers = Position::where('direct_manager_id', $root[0]->id)
            ->whereNotIn('id', $rootAssistant->pluck('id'))
            ->when($this->department_id, fn($query) => $query->where('department_id', $this->department_id))
            ->when($this->program_id, fn($query) => $query->where('program_id', $this->program_id))
            ->where('city_id', 1);


            $managers->with([$this->manager_type.'Team' => function ($query) {
                $query->whereNotNull($this->manager_type.'_manager_id')
                    ->with('activeContracts')
                    ->when($this->city_id, fn($query) => $query->where('city_id', $this->city_id))
                    ->when($this->program_id, fn($query) => $query->where('program_id', $this->program_id))
                    ->when($this->center_id, fn($query) => $query->where('center_id', $this->center_id))
                    ->when($this->department_id, fn($query) => $query->where('department_id', $this->department_id))
                    ->orderBy('job_level_id');
            }]);

        $this->managersCount = $managers->count();
//dd($managers->get());
        return [
            'root' => $root,
            'rootAssistant' => $rootAssistant,
            'managers' => $managers->get(),


        ];
    }

    public function render()
    {

        return view('livewire.position.org-chart', [
            'tree' => $this->buildTree(),
        ]);
    }
}
