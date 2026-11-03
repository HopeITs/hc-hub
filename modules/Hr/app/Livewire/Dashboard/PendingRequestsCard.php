<?php

namespace Modules\Hr\Livewire\Dashboard;

use App\Livewire\Concerns\HandlesLoadFailure;
use App\Livewire\Concerns\IsDashboardTile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;
use Modules\Hr\Models\Checklist;

/** Items the viewer is to hand out (it was a section of Dashboard\Index). */
class PendingRequestsCard extends Component
{
    use HandlesLoadFailure;
    use IsDashboardTile;

    public function render(): View
    {
        return view('livewire.dashboard.pending-requests-card', [
            'pendingRequests' => $this->guardedLoad(fn () => $this->loadPendingRequests(), collect()),
        ]);
    }

    /** @return Collection<int, array{id: int, item: string, employee_name: string, request_date: string, notes: string|null}> */
    protected function loadPendingRequests(): Collection
    {
        $employeeId = auth()->user()?->employee_id;

        if (! $employeeId) {
            return collect();
        }

        return Checklist::with(['item', 'employee'])
            ->where('delivered_id', $employeeId)
            ->where('delivered_status', 'pending')
            ->latest()
            ->get()
            ->map(
                fn (Checklist $checklist): array => [
                    'id' => $checklist->id,
                    'item' => $checklist->item->name ?? '—',
                    'employee_name' => $checklist->employee->name ?? '—',
                    'request_date' => $checklist->created_at->format('Y-m-d'),
                    'notes' => $checklist->notes ?? null,
                ],
            );
    }
}
