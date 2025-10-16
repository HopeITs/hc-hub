<?php
// app/Livewire/OrgChart.php
namespace App\Livewire\Position;

use App\Models\Position;
use Illuminate\Support\Collection;
use Livewire\Component;

class OrgChart extends Component
{
    public array $tree = [];
    public ?int $city_id = null;
    public ?int $program_id = null;
    public ?int $center_id = null;
    public ?int $department_id = null;
    public ?string $manager_type = 'direct'; // 'direct', 'tech', or null

    public function mount(): void
    {
        $this->tree = $this->buildTree();
    }

    public function updated($property): void
    {
        if (in_array($property, ['city_id', 'program_id', 'center_id', 'department_id', 'manager_type'])) {
            $this->tree = $this->buildTree();
        }
    }

    private function buildTree(): array
    {
        $query = Position::with(['jobLevel', 'directTeam.jobLevel']);


        if ($this->city_id) {
            $query->whereIn('city_id',[1, $this->city_id]);
        }
        if ($this->program_id) {
            $query->where('program_id', $this->program_id);
        }
        if ($this->center_id) {
            $query->where('center_id', $this->center_id);
        }
        if ($this->department_id) {
            $query->whereIn('department_id',[1,$this->department_id] );
        }
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
        if($this->manager_type === 'direct') {
            $managers = $positions->filter(fn($p) => $p->direct_manager_id === $root[0]->id &&
                $p->city_id === 1 &&
                in_array($p->jobLevel?->grade, [1, 3, 5]) //program manager ||project manager || department manager
            );
        }else{
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
//dd($this->tree);
        return view('livewire.position.org-chart', [
            'tree' => $this->tree,
        ]);
    }
}
