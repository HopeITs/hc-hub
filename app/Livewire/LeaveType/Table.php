<?php

namespace App\Livewire\LeaveType;

use App\Models\LeaveType;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $showModal = false;
    public $modalTitle = 'Modal title';

    public $item = [
        'name' => [],
        'base_days' => 0,
        'allowed_gender' => null,
        'marital_status' => null,
    ];

    protected function rules()
    {
        return [
            'item.name.*' => 'required|string|max:255',
            'item.base_days' => 'required|integer|min:0',
            'item.allowed_gender' => 'nullable|integer|in:0,1,2',
            'item.marital_status' => 'nullable|integer|in:0,1,2',
        ];
    }

    public function create()
    {
        // Reset the item array to its default state before showing the modal
        $this->reset('item');
        $this->showModal = true;
        $this->modalTitle = __('general.add_new');
    }

    public function edit($id)
    {
        $model = LeaveType::findOrFail($id);
        $this->modalTitle = __('general.edit');
        $this->item = [
            'id' => $model->id,
            'name' => $model->getTranslations('name'),
            'base_days' => $model->base_days,
            'allowed_gender' => $model->allowed_gender,
            'marital_status' => $model->marital_status,
        ];

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->item['name'],
            'base_days' => $this->item['base_days'],
            // Use null if the value is 0 or empty, otherwise cast to int
            'allowed_gender' => !empty($this->item['allowed_gender']) ? (int)$this->item['allowed_gender'] : null,
            'marital_status' => !empty($this->item['marital_status']) ? (int)$this->item['marital_status'] : null,
        ];

        if (array_key_exists('id', $this->item)) {
            LeaveType::findOrFail($this->item['id'])->update($data);
        } else {
            LeaveType::create($data);
        }

        $this->showModal = false;
        $this->reset('item', 'showModal');
        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function cancel()
    {
        $this->showModal = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->reset('item', 'showModal');
    }


    public function render()
    {
        $leaveTypes = LeaveType::paginate(10);

        return view('livewire.leave-type.table', [
            'leaveTypes' => $leaveTypes,
        ]);
    }
}
