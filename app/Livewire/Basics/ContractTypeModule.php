<?php

namespace App\Livewire\Basics;

use App\Models\ContractType;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ContractTypeModule extends Component
{
    protected $listeners = [
        'edit-contract-type' => 'edit',
    ];

    public $showModal = false;
    public $modalTitle = '';

    public $item = [
        'name_ar' => '',
        'name_en' => '',
        'place'   => null,
        'type'    => null,
    ];

    protected function rules()
    {
        return [
            'item.name_ar' => 'required|string',
            'item.name_en' => 'required|string',
            'item.place'   => 'required|integer',
            'item.type'    => 'required|integer',
        ];
    }

    public function create()
    {
        $this->reset('item');
        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    public function edit($id)
    {
        $model = ContractType::findOrFail($id);

        $this->item = [
            'id'      => $model->id,
            'name_ar' => $model->name_ar,
            'name_en' => $model->name_en,
            'place'   => $model->place,
            'type'    => $model->type,
        ];

        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        ContractType::updateOrCreate(
            ['id' => $this->item['id'] ?? null],
            $this->item
        );

        $this->showModal = false;
        $this->reset('item');

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        $this->dispatch('refresh-table');
    }

    public function render()
    {
        return view('livewire.basics.contract-types.index');
    }
}
