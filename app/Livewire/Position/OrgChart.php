<?php
// app/Livewire/OrgChart.php
namespace App\Livewire\Position;

use App\Models\Position;
use Illuminate\Support\Collection;
use Livewire\Component;

class OrgChart extends Component
{
    public array $tree = [];

    public function mount(): void
    {
        $this->tree = $this->buildTree();
    }

    private function buildTree(): array
    {
        $positions = Position::with([
            'jobLevel',
            'directTeam.jobLevel',
        ])->get();

        $root = $positions->firstWhere('direct_manager_id', null);
        if (!$root) return [];

        // Level 1 — board
        $board = $positions->filter(fn($p) =>
            $p->direct_manager_id === $root->id &&
            $p->employee()?->is_board_member &&
            $p->jobLevel?->grade == 2
        );

        // Level 2 — managers
        $managers = $positions->filter(fn($p) =>
            $p->direct_manager_id === $root->id &&
            $p->employee()?->is_board_member &&
            in_array($p->jobLevel?->grade, [3, 5])
        );

        // Teams
        $teams = [];
        foreach ($managers as $m) {
            $assistants = $m->directTeam->filter(fn($t) => $t->jobLevel?->grade == 4);
            $others = $m->directTeam->filter(fn($t) => $t->jobLevel?->grade >= 6);
            $teams[$m->id] = $assistants->merge($others);
        }

        return [
            'root' => $root,
            'board' => $board->values(),
            'managers' => $managers->values(),
            'teams' => $teams,
        ];
    }

    public function render()
    {

        return view('livewire.position.org-chart', [
            'tree' => $this->tree,
        ]);
    }
}
