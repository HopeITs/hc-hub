<?php

namespace App\Livewire\Employee\Tabs;

use App\Livewire\Forms\Employee\ChecklistForm;
use App\Models\ChecklistItem;
use App\Models\Employee;
use Livewire\Component;

class ChecklistTab extends Component
{
    public ChecklistForm $checklistForm;

    public Employee $employee;

    // This property will hold all possible checklist items for the dropdown.
    public $allItems = [];

    /**
     * Mount the component, load the employee, and initialize the form.
     * This is the critical part that fixes the "Undefined variable" error.
     */
    public function mount(Employee $employee)
    {
        $this->employee = $employee;
        $this->checklistForm->setEmployee($this->employee);

        // Fetch all possible checklist items to populate the dropdown.
        $this->allItems = ChecklistItem::orderBy('name')->get();
    }

    /**
     * Add a new blank item to the checklist form.
     */
    public function addChecklistItem()
    {
        $this->checklistForm->addChecklistItem();
    }

    /**
     * Remove an item from the checklist form.
     */
    public function removeChecklistItem(int $index, ?int $id = null)
    {
        $this->checklistForm->removeChecklistItem($index, $id);
    }

    /**
     * Trigger the save method on the form object and show a success message.
     */
    public function saveChecklist()
    {
        $this->checklistForm->save();

        session()->flash('message', 'Checklist saved successfully.');
    }

    /**
     * Render the component's view.
     */
    public function render()
    {
        return view('livewire.employee.tabs.checklist-tab');
    }
}
