<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Livewire\WithFileUploads;

class ChecklistForm extends Form
{
    use WithFileUploads;

    public ?Employee $employee = null ;


    protected function rules()
    {
        return [

        ];
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;

    }




    public function addChecklist(): void
    {
        $this->checklists[] = [
            'item_id' => null,
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

    public function removeChecklist(int $index): void
    {
        unset($this->checklists[$index]);
        $this->checklists = array_values($this->checklists);
    }

    public function saveChecklist(): void
    {
        $this->validate([
            'checklists' => 'array',
            'checklists.*.item_id' => 'required_with:checklists.*.brand|exists:checklist_items,id|nullable',
        ]);

        $this->employee->checklists()->delete();
        $this->employee->checklists()->createMany($this->checklists);
    }
}
