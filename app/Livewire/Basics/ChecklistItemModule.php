<?php

namespace App\Livewire\Basics;

use App\Models\ChecklistItem;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ChecklistItemModule extends Component
{
    public $showModal = false;
    public $modalTitle = '';
    public $item = [
        'id' => null,
        'name' => null,
        'order' => null,
    ];

    protected $rules = [
        'item.name' => 'required|string',
        'item.order' => 'required|integer',
    ];

    protected $listeners = ['editChecklistItem' => 'edit'];

    public function create()
    {
        $this->modalTitle = __('general.add-new');
        $this->item = ['id' => null, 'name' => null, 'order' => null];
        $this->showModal = true;
    }

    public function edit($id)
    {
        $model = ChecklistItem::findOrFail($id);
        $this->modalTitle = __('general.edit');
        $this->item = [
            'id' => $model->id,
            'name' => $model->name,
            'order' => $model->order,
        ];
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->item['id']) {
            ChecklistItem::findOrFail($this->item['id'])->update([
                'name' => $this->item['name'],
                'order' => $this->item['order'],
            ]);
        } else {
            ChecklistItem::create([
                'name' => $this->item['name'],
                'order' => $this->item['order'],
            ]);
        }

        $this->showModal = false;
        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function render()
    {
        return view('livewire.basics.checklist-item-module');
    }
}
