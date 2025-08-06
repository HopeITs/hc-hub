<?php

namespace App\Livewire\Basics;


use App\Models\ContractType;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class ContractTypeModule extends Component
{
    use WithPagination;

    public $showModal = false;
    public $modalTitle = 'Modal title';


    public $item = [
        'type' => [],
        'place' => null,
        'weekly_hours' => null,
    ];

    protected function rules()
    {
        return [

            'item.type.ar' => 'required|string',
            'item.type.en' => 'required|string',
            'item.place' => 'required|integer',
            'item.weekly_hours' => 'required|integer',
        ];
    }



    public function create()
    {
        $this->showModal = true;
        $this->modalTitle = __('general.add_new');
    }


    public function edit($id)
    {
        $model = ContractType::findOrFail($id);
        $this->modalTitle = __('general.edit');
        $this->item = [
            'id' => $model->id,
            'type' => $model->getTranslations('type'),
            'place' => $model->place,
            'weekly_hours' => $model->weekly_hours,
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
            ContractType::findOrFail($this->item['id'])->update([
                'type' => $this->item['type'],
                'place' => $this->item['place'],
                'weekly_hours' => $this->item['weekly_hours'],
            ]);
        } else {
            ContractType::create([
                'type' => $this->item['type'],
                'weekly_hours' => $this->item['weekly_hours'],
                'place' => $this->item['place'],
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

        ContractType::find($id['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

    }



    public function render()
    {

        $items = ContractType::paginate(15);

        return view('livewire.basics.contract-type-module', [
            'items' => $items,
        ]);

    }
}
