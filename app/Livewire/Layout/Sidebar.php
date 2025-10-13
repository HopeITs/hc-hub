<?php

namespace App\Livewire\Layout;

use App\Models\Checklist;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Sidebar extends Component
{
    #[On('pendingDeliversCount')]
    public function refreshPendingDeliversCount()
    {
        // This method only exists to trigger a re-render
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
        return view('livewire.layout.sidebar',compact('user', 'employee',
                'employeeId',
                'departmentName',
                'isDeliverResponsible',
                'pendingDeliversCount',
                'pendingReturnsCount'));
    }
}
