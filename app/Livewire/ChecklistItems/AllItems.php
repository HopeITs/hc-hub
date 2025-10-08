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
            ->where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->whereNull('returned_status')->orWhereNot('returned_status', 'received');
            })
            ->latest('updated_at')
            ->get()
            ->map(function ($item) {
                $item->display_status = match ($item->delivered_status) {
                    'delivered' => 'Delivered',
                    default => 'On Hold',
                };
                return $item;
            });
    }

    public function render()
    {
        return view('livewire.checklist-items.all', [
            'items' => $this->items,
        ]);
    }
}
