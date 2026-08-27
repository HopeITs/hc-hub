<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Checklist;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\ChecklistAssigned;
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
                $isFullyReturned = $saved?->returned_status === 'received';
                $deliveredId = $saved?->delivered_id;

                if ($isFullyReturned || $deliveredId === null) {
                    $defaultDeliverer = $defaultDeliverables->first(fn ($d) => $d->item_id === $item->id && $d->city_id === $employee->city_id);
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
        $newNotificationSent = false;
        $currentProfileId = auth()->user()->profile?->id;

        foreach ($this->items as $itemData) {
            $existing = $this->employee->checklists()->where('item_id', $itemData['item_id'])->latest()->first();

            $original_delivered_id = $existing ? $existing->delivered_id : null;
            $original_receiver_id = $existing ? $existing->receiver_id : null;

            if (empty($itemData['is_selected']) && (! $existing || $existing->delivered_status !== 'delivered')) {
                $this->employee->checklists()->where('item_id', $itemData['item_id'])->delete();

                continue;
            }

            if (! empty($itemData['is_selected'])) {
                $deliveredId = $itemData['delivered_id'] ?: $currentProfileId;

                $isFullyReturned = $existing && $existing->returned_status === 'received';

                $values = [
                    'delivered_id' => $deliveredId,
                ];

                if ($isFullyReturned || $existing === null || $existing->delivered_status === null) {
                    $values = array_merge($values, [
                        'item_id' => $itemData['item_id'],
                        'employee_id' => $this->employee->id,
                        'delivered_status' => 'pending',
                        'delivered_at' => null,
                        'receiver_id' => null,
                        'returned_status' => null,
                        'returned_at' => null,
                        'notes' => null,
                    ]);

                    $checklist = Checklist::create($values);
                } else {
                    $values['delivered_status'] = $existing->delivered_status;
                    $values['delivered_at'] = $existing->delivered_at;

                    if (! empty($itemData['receiver_id'])) {
                        $values['receiver_id'] = $itemData['receiver_id'];
                        if ($existing->returned_status !== 'pending' && $existing->returned_status !== 'received') {
                            $values['returned_status'] = 'pending';
                        }
                    } else {
                        $values['receiver_id'] = null;
                        $values['returned_status'] = null;
                        $values['returned_at'] = null;
                    }

                    $existing->update($values);
                    $checklist = $existing;
                }

                $checklist->refresh();

                $isNewlyAssignedForDelivery = (
                    ! $original_delivered_id ||
                    ($checklist->delivered_id !== $original_delivered_id && $checklist->delivered_status === 'pending')
                ) && $checklist->delivered_id && $checklist->delivered_id !== $currentProfileId;

                if ($isNewlyAssignedForDelivery) {
                    $deliverer = User::whereHas('profile', fn ($q) => $q->where('id', $checklist->delivered_id))->first();
                    if ($deliverer) {
                        $deliverer->notify(new ChecklistAssigned($checklist, 'deliver'));
                        $newNotificationSent = true;
                    }
                }

                $isNewlyAssignedForReturn = (
                    $checklist->receiver_id &&
                    $checklist->returned_status === 'pending' &&
                    $checklist->receiver_id !== $original_receiver_id &&
                    $checklist->receiver_id !== $currentProfileId
                );

                if ($isNewlyAssignedForReturn) {
                    $receiver = User::whereHas('profile', fn ($q) => $q->where('id', $checklist->receiver_id))->first();
                    if ($receiver) {
                        $receiver->notify(new ChecklistAssigned($checklist, 'receive'));
                        $newNotificationSent = true;
                    }
                }
            }
        }

        if ($newNotificationSent) {
            $this->component->dispatch('notification-created');
        }
    }
}
