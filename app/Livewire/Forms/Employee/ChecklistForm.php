<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Checklist;
use App\Models\Employee;
use Livewire\Form;

class ChecklistForm extends Form
{
    public ?Employee $employee = null;
    public array $checklists = [];

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
        $this->checklists = $employee->checklists()->get()->toArray();
    }

    public function rules(): array
    {
        return [
            'checklists' => ['array'],
            'checklists.*.item_id' => ['required', 'integer', 'exists:checklist_items,id'],
            'checklists.*.delivered_id' => ['nullable', 'integer', 'exists:employees,id'],
            'checklists.*.brand' => ['nullable', 'string', 'max:255'],
            'checklists.*.model' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function addChecklist(): void
    {
        $this->checklists[] = [
            'item_id' => null,
            'delivered_id' => null,
            'brand' => '',
            'model' => '',
            'serial' => '',
            'delivered_at' => now()->format('Y-m-d'),
            'delivered_status' => '',
            'returned_at' => null,
            'returned_status' => '',
            'notes' => '',
        ];
    }

    public function removeChecklist(int $index, ?int $id = null): void
    {
        if ($id) {
            Checklist::find($id)?->delete();
        }
        unset($this->checklists[$index]);
        $this->checklists = array_values($this->checklists);
    }

    public function save(): void
    {
        $this->validate();

        foreach ($this->checklists as $data) {
            $this->employee->checklists()->updateOrCreate(
                ['id' => $data['id'] ?? null],
                $data
            );
        }
    }
}
