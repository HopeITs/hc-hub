<?php

namespace App\Livewire\Basics;

use App\Models\LeaveType;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class LeaveTypeModule extends Component
{
    use WithPagination;

    public $showModal = false;
    public $modalTitle = 'Modal title';

    public $item = [
        'name' => [],
        'base_days' => null,
        'allowed_gender' => null,
        'marital_status' => null,
    ];

    protected $rules = [
        'item.name.*' => 'required|string',
        'item.base_days' => 'required|integer',
        'item.allowed_gender' => 'integer',
        'item.marital_status' => 'integer',
    ];

    public function create()
    {
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

        if (array_key_exists('id', $this->item)) {
            LeaveType::findOrFail($this->item['id'])->update($this->item);
        } else {
            LeaveType::create($this->item);
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
        LeaveType::findOrFail($id['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function cancel()
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->reset('item', 'showModal');
    }

    public function render()
    {
        return view('livewire.basics.leave-type-module', [
            'items' => LeaveType::paginate(15),

        ]);
    }
}
