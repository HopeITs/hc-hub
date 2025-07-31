<?php

namespace App\Livewire\ContractType;


use App\Models\ContractType;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $showEditModal = false;
    public $editing = [
        'type' => [],
        'place' => null,
        'weekly_hours' => null,
    ];

    protected $rules = [
        'editing.type.*' => 'required',
        'editing.place' => 'required|integer',
        'editing.weekly_hours' => 'required|integer',
    ];



    public function edit($id)
    {
        $model = ContractType::findOrFail($id);

        $this->editing = [
            'id' => $model->id,
            'type' =>  $model->getTranslations('type'),
            'place' => $model->place,
            'weekly_hours' => $model->weekly_hours,
        ];


        $this->showEditModal = true;
    }

    public function save()
    {
        $this->validate();

        $model = ContractType::findOrFail($this->editing['id']);
        $model->update([
            'type' => $this->editing['type'],
            'place' => $this->editing['place'],
            'weekly_hours' => $this->editing['weekly_hours'],
        ]);
        $this->showEditModal = false;
        LivewireAlert::title(__('general.status-updated'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function render()
    {

        $items = ContractType::paginate(10);

        return view('livewire.contract-type.table', [
            'items' => $items,
        ]);

    }
}
