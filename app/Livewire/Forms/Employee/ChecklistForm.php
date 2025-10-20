<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Checklist;
use App\Models\Employee;
use Illuminate\Support\Collection;
use Livewire\Form;

class ChecklistForm extends Form
{
    public ?Employee $employee = null;
    public array $items = [];

    public function setEmployeeAndAllItems(Employee $employee, Collection $allItems, Collection $defaultDeliverables): void
    {
        $this->employee = $employee;
        $savedChecklists = $employee->checklists->keyBy('item_id');
        $isNew = $savedChecklists->isEmpty();

        $this->items = $allItems
            ->map(function ($item) use ($savedChecklists, $isNew, $defaultDeliverables, $employee) {
                $saved = $savedChecklists->get($item->id);
                $defaultDeliverer = $defaultDeliverables->first(fn($d) => $d->item_id === $item->id && $d->city_id === $employee->city_id);

                $isReturned = $saved?->returned_status === 'received';

                $deliveredId = $saved?->delivered_id;
                if ($deliveredId === null) {
                    $deliveredId = $defaultDeliverer?->employee_id;
                }

                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'is_selected' => $isNew || (bool) $saved,

                    'delivered_id' => $deliveredId,

                    'receiver_id' => $isReturned ? null : $saved?->receiver_id,

                    'delivered_status' => $saved?->delivered_status,
                    'returned_status' => $saved?->returned_status,
                    'can_edit' => true,
                ];
            })
            ->toArray();
    }

    public function save(): void
    {
        foreach ($this->items as $itemData) {
            $existing = $this->employee->checklists()->where('item_id', $itemData['item_id'])->first();

            if (empty($itemData['is_selected']) && (!$existing || $existing->delivered_status !== 'delivered')) {
                $this->employee->checklists()->where('item_id', $itemData['item_id'])->delete();
                continue;
            }

            if ($existing && $existing->returned_status === 'received') {
                continue;
            }

            if (!empty($itemData['is_selected'])) {
                $deliveredId = $itemData['delivered_id'] ?: auth()->user()->profile?->id;

                $values = [
                    'delivered_id' => $deliveredId,
                ];

                if (!$existing || $existing->delivered_status === null) {
                    $values['delivered_status'] = 'pending';
                    $values['delivered_at'] = now();
                }

                if ($existing && $existing->delivered_status === 'delivered') {
                    $values['delivered_status'] = 'delivered';

                    if (!empty($itemData['receiver_id'])) {
                        $values['receiver_id'] = $itemData['receiver_id'];
                        if ($existing->returned_status !== 'pending') {
                            $values['returned_status'] = 'pending';
                            $values['returned_at'] = now();
                        }
                    } else {
                        $values['receiver_id'] = null;
                        $values['returned_status'] = null;
                        $values['returned_at'] = null;
                    }
                }

                $this->employee->checklists()->updateOrCreate(
                    ['item_id' => $itemData['item_id']],
                    $values
                );
            }
        }
    }
}
