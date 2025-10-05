<?php

namespace App\Livewire\ChecklistItems;

use App\Models\Checklist;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AllItems extends Component
{
    public function getItemsProperty()
    {
        $employee = Auth::user()->profile ?? null;
        if (!$employee) {
            return collect();
        }

        return Checklist::with(['employee', 'item'])
            ->where(function ($q) use ($employee) {
                $q->where('delivered_id', $employee->id)
                  ->orWhere('receiver_id', $employee->id);
            })
            ->where(function ($q) {
                $q->where('delivered_status', 'delivered')
                  ->orWhere('returned_status', 'received');
            })
            ->latest('updated_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.checklist-items.all', [
            'items' => $this->items,
        ]);
    }
}
