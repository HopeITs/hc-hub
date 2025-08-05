<?php

namespace App\Livewire\Basics;

use App\Models\Employee;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class EmployeeModule extends Component
{
    use WithPagination;

    public $showModal = false;
    public $modalTitle = '';
    public $item = [];

    protected $rules = [
    'item.full_name' => 'required|string|max:255',
    'item.years_of_service' => 'nullable|integer',
    'item.direct_manager_id' => 'nullable|integer',
    'item.tech_manager_id' => 'nullable|integer',
    'item.national_number' => 'nullable|string',
    'item.dob' => 'nullable|date',
    'item.actual_job' => 'nullable|string',
    'item.civil_registry_office' => 'nullable|string',
    'item.civil_registry_number' => 'nullable|string',
    'item.place_of_registration' => 'nullable|string',
    'item.gender' => 'nullable|string',
    'item.father_name' => 'nullable|array',
    'item.mother_name' => 'nullable|array',
    'item.nationality' => 'nullable|string',
    'item.marital_status' => 'nullable|string',
    'item.children' => 'nullable|integer',
    'item.work_phone' => 'nullable|string',
    'item.personal_phone' => 'nullable|string',
    'item.personal_email' => 'nullable|email',
    'item.work_email' => 'required|email',
    'item.address' => 'nullable|array',
    'item.skills' => 'nullable|string',
    'item.interests' => 'nullable|string',
    'item.is_active' => 'boolean',
];

    public function create()
    {
        $this->reset('item');
        $this->item['is_active'] = true;
        $this->modalTitle = __('general.add_new');
        $this->showModal = true;
    }

    public function edit($id)
    {
        $model = Employee::findOrFail($id);
        $this->item = $model->toArray();
        $this->item['id'] = $model->id;
        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if (isset($this->item['id'])) {
            Employee::findOrFail($this->item['id'])->update($this->item);
        } else {
            Employee::create($this->item);
        }

        $this->reset('item', 'showModal');

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function confirmDelete($id)
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.primary'))
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete($id)
    {
        Employee::findOrFail($id['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function render()
    {
        $items = Employee::with(['directManager', 'techManager', 'jobLevel', 'contractType', 'department'])->paginate(15);

        return view('livewire.employees.index', compact('items'));
    }
}
