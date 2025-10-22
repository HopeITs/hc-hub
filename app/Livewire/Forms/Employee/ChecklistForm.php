<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Checklist;
use App\Models\Employee;
use Illuminate\Support\Collection;
use App\Models\User;
use App\Notifications\ChecklistAssigned;
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
                $isFullyReturned = $saved?->returned_status === 'received';
                $deliveredId = $saved?->delivered_id;

                if ($isFullyReturned || $deliveredId === null) {
                    $defaultDeliverer = $defaultDeliverables->first(fn($d) => $d->item_id === $item->id && $d->city_id === $employee->city_id);
                    $deliveredId = $defaultDeliverer?->employee_id;
                }
                if ($isFullyReturned) {
                    $deliveredId = $defaultDeliverer?->employee_id;
                }

                $isSelected = $isNew || (bool) $saved;
                return [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'is_selected' => $isSelected,

                    'delivered_id' => $deliveredId,

                    'receiver_id' => $isFullyReturned ? null : $saved?->receiver_id,

                    'delivered_status' => $isFullyReturned ? null : $saved?->delivered_status,
                    'returned_status' => $isFullyReturned ? null : $saved?->returned_status,
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

            if (!empty($itemData['is_selected'])) {
                $deliveredId = $itemData['delivered_id'] ?: auth()->user()->profile?->id;

                $isFullyReturned = $existing && $existing->returned_status === 'received';

                $values = [
                    'delivered_id' => $deliveredId,
                ];

                if ($isFullyReturned || $existing === null || $existing->delivered_status === null) {
                    $values = array_merge($values, [
                        'delivered_status' => 'pending',
                        'delivered_at' => now(),
                        'receiver_id' => null,
                        'returned_status' => null,
                        'returned_at' => null,
                        'notes' => null,
                    ]);
                } else {
                    $values['delivered_status'] = $existing->delivered_status;
                    $values['delivered_at'] = $existing->delivered_at;

                    if (!empty($itemData['receiver_id'])) {
                        $values['receiver_id'] = $itemData['receiver_id'];
                        if ($existing->returned_status !== 'pending' && $existing->returned_status !== 'received') {
                            $values['returned_status'] = 'pending';
                        }
                    } else {
                        $values['receiver_id'] = null;
                        $values['returned_status'] = null;
                        $values['returned_at'] = null;
                    }
                }

                $checklist = $this->employee->checklists()->updateOrCreate(['item_id' => $itemData['item_id']], $values);

                if (!empty($itemData['delivered_id']) && $itemData['delivered_id'] != auth()->user()->profile?->id) {
                    $deliverer = User::whereHas('profile', fn($q) => $q->where('id', $itemData['delivered_id']))->first();
                    if ($deliverer) {
                        $deliverer->notify(new ChecklistAssigned($checklist, 'deliver'));
                    }
                }

                if (!empty($itemData['receiver_id']) && $itemData['receiver_id'] != auth()->user()->profile?->id) {
                    $receiver = User::whereHas('profile', fn($q) => $q->where('id', $itemData['receiver_id']))->first();
                    if ($receiver) {
                        $receiver->notify(new ChecklistAssigned($checklist, 'receive'));
                    }
                }
            }
        }
    }
}
