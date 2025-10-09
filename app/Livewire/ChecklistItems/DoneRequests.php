<?php

namespace App\Livewire\ChecklistItems;

use App\Models\Checklist;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DoneRequests extends Component
{
    public function getItemsProperty()
    {
        $employee = Auth::user()->profile ?? null;
        if (!$employee) {
            return collect();
        }

        $department = $employee->contracts()->with('position.department')->first()?->position?->department?->name;

        if (!in_array($department, ['IT', 'Communication', 'HR'])) {
            return collect();
        }

        $deptEmployeeIds = \App\Models\Employee::whereHas('contracts.position.department', function ($q) use ($department) {
            $q->where('name', $department);
        })->pluck('id');

        $delivered = Checklist::with(['employee', 'item', 'delivered', 'receiver'])
            ->whereIn('delivered_id', $deptEmployeeIds)
            ->where('delivered_status', 'delivered')
            ->get()
            ->map(
                fn($r) => [
                    'employee' => $r->delivered?->name ?? '—',
                    'action' => 'Delivered',
                    'item' => $r->item?->name ?? '—',
                    'counterparty' => $r->receiver?->name ?? ($r->employee?->name ?? '—'),
                    'date' => $r->delivered_at?->format('Y-m-d'),
                    'notes' => $r->notes ?? '-',
                ],
            );

        $received = Checklist::with(['employee', 'item', 'delivered', 'receiver'])
            ->whereIn('receiver_id', $deptEmployeeIds)
            ->where('returned_status', 'received')
            ->get()
            ->map(
                fn($r) => [
                    'employee' => $r->receiver?->name ?? '—',
                    'action' => 'Received',
                    'item' => $r->item?->name ?? '—',
                    'counterparty' => $r->employee?->name ?? ($r->delivered?->name ?? '—'),
                    'date' => $r->returned_at?->format('Y-m-d'),
                    'notes' => $r->notes ?? '-',
                ],
            );

        return $delivered->merge($received)->sortByDesc('date')->values();
    }

    public function render()
    {
        return view('livewire.checklist-items.done-requests', [
            'items' => $this->items,
        ]);
    }
}
