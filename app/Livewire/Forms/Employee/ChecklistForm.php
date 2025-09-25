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
        $defaults = $defaultDeliverables->keyBy('item_id');

        $this->items = $allItems->map(function ($item) use ($savedChecklists, $defaults) {
            $saved = $savedChecklists->get($item->id);
            $default = $defaults->get($item->id);

            return [
                'item_id' => $item->id,
                'item_name' => $item->name,
                // Check the box if a value is already saved OR if there is a default
                'is_selected' => (bool) $saved || (bool) $default,
                // Use the saved value first, otherwise fall back to the default value
                'delivered_id' => $saved?->delivered_id ?? $default?->employee_id,
            ];
        })->toArray();
    }

    public function save(): void
    {
        foreach ($this->items as $itemData) {
            if ($itemData['is_selected']) {
                $valuesToSave = [
                    'delivered_id' => $itemData['delivered_id'] ?: auth()->id(),
                    'delivered_at' => now(),
                ];
                $this->employee->checklists()->updateOrCreate(
                    ['item_id' => $itemData['item_id']],
                    $valuesToSave
                );
            } else {
                $this->employee->checklists()->where('item_id', $itemData['item_id'])->delete();
            }
        }
    }
}
