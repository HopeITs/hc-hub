<?php

namespace Modules\Hr\Livewire\Dashboard;

use App\Livewire\Concerns\HandlesLoadFailure;
use App\Livewire\Concerns\IsDashboardTile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Modules\Hr\Models\Badge;

/** The viewer's own badges and warnings, newest first (it was a section of Dashboard\Index). */
class BadgesCard extends Component
{
    use HandlesLoadFailure;
    use IsDashboardTile;

    public function render(): View
    {
        return view('livewire.dashboard.badges-card', [
            'myBadges' => $this->guardedLoad(fn () => $this->loadMyBadges(), collect()),
        ]);
    }

    /** @return Collection<int, Badge> */
    protected function loadMyBadges(): Collection
    {
        $employeeId = auth()->user()?->employee_id;

        if (! $employeeId) {
            return collect();
        }

        return Badge::query()->with('employee')->where('employee_id', $employeeId)->orderByDesc('date')->get();
    }
}
