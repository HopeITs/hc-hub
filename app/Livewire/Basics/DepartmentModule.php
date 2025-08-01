<?php

namespace App\Livewire\Basics;

use App\Models\Department;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class DepartmentModule extends Component
{
    use WithPagination;

    public $showModal = false;
    public $modalTitle = '';
    public $item = [
        'name' => [],
        'description' => [],
        'email' => null,
    ];

    protected $rules = [
        'item.name.*' => 'required|string|max:255',
        'item.description.*' => 'nullable|string',
        'item.email' => 'nullable|email|max:255',
    ];

    public function render()
    {
        $departments = Department::paginate(10);

        return view('livewire.basics.department-module', compact('departments'));
    }

    public function create()
    {
        $this->reset('item');
        $this->modalTitle = __('general.add_new');
        $this->showModal = true;
    }

    public function edit($id)
    {
        $model = Department::findOrFail($id);

        $this->item = [
            'id' => $model->id,
            'name' => $model->getTranslations('name'),
            'description' => $model->getTranslations('description'),
            'email' => $model->email,
        ];

        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->item['name'],
            'description' => $this->item['description'],
            'email' => $this->item['email'],
        ];

        if (isset($this->item['id'])) {
            Department::findOrFail($this->item['id'])->update($data);
        } else {
            Department::create($data);
        }

        $this->reset(['item', 'showModal']);
        $this->alert('success', __('general.success-message'));
    }

    public function confirmDelete($id)
    {
        $this->alert('question', __('general.delete-item'), [
            'text' => __('general.confirm-delete-item'),
            'showCancelButton' => true,
            'confirmButtonText' => __('general.delete'),
            'cancelButtonText' => __('general.cancel'),
            'onConfirmed' => 'delete',
            'params' => ['id' => $id],
        ]);
    }

    public function delete($payload)
    {
        Department::findOrFail($payload['id'])->delete();
        $this->alert('success', __('general.success-message'));
    }

    public function cancel()
    {
        $this->reset(['item', 'showModal']);
        $this->resetValidation();
    }
}
