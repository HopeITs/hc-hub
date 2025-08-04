<?php

namespace App\Livewire\Basics;

use App\Models\ChecklistItem;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ChecklistItemModule extends Component
{

    use WithPagination;

    public $showModal = false;
    public $modalTitle = 'Modal title';


    public $item = [
        'name' => null,
        'order' => null,
    ];

    protected $rules = [
        'item.name' => 'required|string',
        'item.order' => 'required|integer',
    ];

    public function create()
    {
        $this->showModal = true;
        $this->modalTitle = __('general.add_new');
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

    public function save()
    {
        $this->validate();

        if (array_key_exists('id',$this->item)) {
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
        $this->reset('item', 'showModal');
        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }
    public function delete($id)
    {

        ChecklistItem::find($id['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

    }



    public function render()
    {

        $items = ChecklistItem::paginate(15);

        return view('livewire.basics.checklist-item-module', [
            'items' => $items,
        ]);

    }
}
