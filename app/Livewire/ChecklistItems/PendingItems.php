<?php

namespace App\Livewire\ChecklistItems;

use App\Models\Checklist;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PendingItems extends Component
{
    public array $notes = [];

    public function getItemsProperty()
    {
        $employee = Auth::user()->profile ?? null;
        if (!$employee) {
            return collect();
        }

        return Checklist::with(['employee', 'item'])
            ->where('delivered_id', $employee->id)
            ->where('delivered_status', 'pending')
            ->latest()
            ->get();
    }

    public function markDelivered(int $id): void
    {
        $checklist = Checklist::findOrFail($id);
        $checklist->update([
            'delivered_status' => 'delivered',
            'delivered_at' => now(),
            'notes' => $this->notes[$id] ?? null,
        ]);

        unset($this->notes[$id]);
        session()->flash('message', __('general.item_marked_delivered'));
    }

    public function render()
    {
        return view('livewire.checklist-items.pending', [
            'pendingItems' => $this->items,
        ]);
    }
}
