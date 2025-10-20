<?php

namespace App\Livewire\ChecklistItems;

use App\Models\Checklist;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PendingReturnsCounter extends Component
{
    protected $listeners = ['pendingReturnsCount' => '$refresh'];

    public function getPendingReturnsCountProperty(): int
    {
        $employee = Auth::user()->profile ?? null;
        if (!$employee) {
            return 0;
        }

        return Checklist::where('receiver_id', $employee->id)
            ->where('returned_status', 'pending')
            ->count();
    }

    public function render()
    {
        return view('livewire.checklist-items.pending-returns-counter', [
            'pendingReturnsCount' => $this->getPendingReturnsCountProperty(),
        ]);
    }
}
