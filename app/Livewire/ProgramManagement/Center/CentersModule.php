<?php

namespace App\Livewire\ProgramManagement\Center;

use App\Models\Center;
use App\Models\City;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

class CentersModule extends Component
{
    public bool $showModal = false;
    public string $modalTitle = '';

    public array $item = [
        'id' => null,
        'name' => '',
        'city_id' => '',
        'address_ar' => '',
        'address_en' => '',
        'is_active' => true,
        'weekends' => ''
    ];

    protected $rules = [
        'item.name' => 'required|string|max:255',
        'item.city_id' => 'required|exists:cities,id',
        'item.address.en' => 'nullable|string|max:255',
        'item.address.ar' => 'nullable|string|max:255',
        'item.weekends' => 'required'
    ];

    public function render()
    {
        $locale = app()->getLocale();
        $cities = City::orderBy(transColumn('name'))->get(['id', transColumn('name')]);

        return view('livewire.program-management.centers-module', compact('cities'));
    }

    public function create(): void
    {
        $this->item = [
            'id' => null,
            'name' => '',
            'city_id' => '',
            'address_ar' => '',
            'address_en' => '',
            'is_active' => true,
            'weekends' => ''
        ];

        $this->resetValidation();
        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    #[On('openCenterEdit')]
    public function edit(int $id): void
    {
        $model = Center::findOrFail($id);

        $this->item = [
            'id' => $model->id,
            'name' => $model->name,
            'city_id' => $model->city_id,
            'address_ar' => $model->address_ar,
            'address_en' => $model->address_en,
            'is_active' => (bool)$model->is_active,
            'weekends' => $model->weekends
        ];

        $this->resetValidation();
        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }

    public function save(): void
    {

        $this->validate();

        $model = Center::findOrNew($this->item['id']);
        $model->fill($this->item);
        $model->save();

        $this->showModal = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('closeModal');

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
