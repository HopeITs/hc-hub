<?php

namespace App\Livewire\Basics;

use App\Models\ContractType;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class ContractTypeModule extends Component
{
    protected $listeners = ['openContractTypeEdit' => 'edit'];

    public bool $showModal = false;
    public string $modalTitle = '';

    public array $item = [
        'id' => null,
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
            'item.place'   => 'required|integer',
            'item.type'    => 'required|integer',
        ];
    }

    public function render()
    {
        return view('livewire.basics.contract-type-module');
    }

    public function create(): void
    {
        $this->item = [
            'id' => null,
            'name_ar' => '',
            'name_en' => '',
            'place' => null,
            'type' => null,
        ];

        $this->resetValidation();
        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }


    public function edit(int $id): void
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


    public function save(): void
    {
        $this->validate();

        $model = !empty($this->item['id'])
            ? ContractType::findOrFail($this->item['id'])
            : new ContractType();

        $model->fill($this->item);
        $model->save();

        $this->showModal = false;

        $this->dispatch('refreshContractTypeTable');

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function delete(array $payload): void
    {
        ContractType::findOrFail($payload['id'])->delete();

        $this->dispatch('refreshContractTypeTable');

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }
}