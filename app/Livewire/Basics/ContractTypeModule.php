<?php

namespace App\Livewire\Basics;


use App\Models\ContractType;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\SortableTable;

class ContractTypeModule extends Component
{
    use WithPagination, SortableTable;

    public $showModal = false;
    public $modalTitle = 'Modal title';

    public $search = '';
    public $item = [
        'name_ar' => '',
        'name_en' => '',
        'place' => null,
        'type' => null,
    ];

    protected function rules()
    {
        return [

            'item.name_ar' => 'required|string',
            'item.name_en' => 'required|string',
            'item.place' => 'required|integer',
            'item.type' => 'required|integer',
        ];
    }


    public function create()
    {
        $this->showModal = true;
        $this->modalTitle = __('general.add-new');
    }


    public function edit($id)
    {
        $model = ContractType::findOrFail($id);
        $this->modalTitle = __('general.edit');
        $this->item = [
            'id' => $model->id,
            'name_ar' => $model->name_ar,
            'name_en' => $model->name_en,
            'place' => $model->place,
            'type' => $model->type,
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

        if (array_key_exists('id', $this->item)) {
            ContractType::findOrFail($this->item['id'])->update([
                'type' => $this->item['type'],
                'place' => $this->item['place'],
                'name_ar' => $this->item['name_ar'],
                'name_en' => $this->item['name_en'],
            ]);
        } else {
            ContractType::create([
                'type' => $this->item['type'],
                'name_ar' => $this->item['name_ar'],
                'name_en' => $this->item['name_en'],
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

        $sort   = $this->sortField ?: 'id';
        $dir    = $this->sortDirection ?: 'asc';

        $query = ContractType::query()
            ->when($this->search, function ($q){
                $term = $this->search;

                $q->where(function ($w) use ($term) {
                    // Search in translated name
                    $w->search(["name_ar","name_en" , "place" , "type"], $term);
                });
            });

        // Sorting logic
        switch ($sort) {
            case 'name_ar':
                $query->orderBy("name->ar", $dir);
                break;
            case 'name_en':
                $query->orderBy("name->en", $dir);
                break;
            case 'place':
            case 'type':
                $query->orderBy($sort, $dir);
                break;
            default:
                $query->orderBy('id', $dir);
                break;
        }

        $items = $query->paginate(15);

        return view('livewire.basics.contract-type-module', compact('items'));
    }

}
