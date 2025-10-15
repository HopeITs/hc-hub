<?php

namespace App\Livewire\Layout;

use App\Models\Checklist;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    #[On('pendingDeliversCount')]
    #[On('pendingReturnsCount')]
    public function refreshCounts(): void
    {
        // Trigger re-render when either event fires
    }

    public function render()
    {
        $employee = Auth::user()->profile ?? null;
        $employeeId = $employee->id ?? null;
        $departmentName = $employee?->contracts()->with('position.department')->first()?->position?->department?->name;

        $isDeliverResponsible = in_array($departmentName, ['IT', 'Communication', 'HR']);

        $pendingDeliversCount = $employeeId
            ? Checklist::where('delivered_id', $employeeId)
                ->where('delivered_status', '!=', 'delivered')
                ->count()
            : 0;

        $pendingReturnsCount = $employeeId
            ? Checklist::where('receiver_id', $employeeId)
                ->where('returned_status', 'pending')
                ->count()
            : 0;

        $user = Auth::user();

        return view('livewire.layout.sidebar', [
            'user' => $user,
            'employee' => $employee,
            'employeeId' => $employeeId,
            'departmentName' => $departmentName,
            'isDeliverResponsible' => $isDeliverResponsible,
            'pendingDeliversCount' => $pendingDeliversCount,
            'pendingReturnsCount' => $pendingReturnsCount,
        ]);
    }
}
