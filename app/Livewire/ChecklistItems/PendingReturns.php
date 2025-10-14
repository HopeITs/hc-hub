<?php

namespace App\Livewire\ChecklistItems;

use App\Models\Checklist;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PendingReturns extends Component
{
    public array $notes = [];

    public function getItemsProperty()
    {
        $employee = Auth::user()->profile ?? null;
        if (!$employee) {
            return collect();
        }

        return Checklist::with(['employee', 'item'])
            ->where('receiver_id', $employee->id)
            ->where('returned_status', 'pending')
            ->latest()
            ->get();
    }

    public function markReceived(int $id): void
    {
        $checklist = Checklist::findOrFail($id);
        $checklist->update([
            'returned_status' => 'received',
            'returned_at' => now(),
            'notes' => $this->notes[$id] ?? null,
        ]);

        unset($this->notes[$id]);
        $this->dispatch('pendingReturnsCount');
        session()->flash('message', __('general.item_marked_received'));
    }

    public function render()
    {
        return view('livewire.checklist-items.pending-returns', [
            'pendingReturns' => $this->items,
        ]);
    }
}
