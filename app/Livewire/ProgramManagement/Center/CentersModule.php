<?php

namespace App\Livewire\ProgramManagement\Center;

use App\Models\Center;
use App\Models\City;
use App\Notifications\NewCenterCreated;
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
    ];

    protected $rules = [
        'item.name' => 'required|string|max:255',
        'item.city_id' => 'required|exists:cities,id',
        'item.address_en' => 'nullable|string|max:255',
        'item.address_ar' => 'nullable|string|max:255',
    ];

    public function render()
    {
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
        ];

        $this->resetValidation();
        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $model = Center::findOrNew($this->item['id']);
        $isNew = !$model->exists;

        $model->fill([
            'name' => $this->item['name'],
            'city_id' => $this->item['city_id'],
            'is_active' => $this->item['is_active'],
            'address_ar' => $this->item['address_ar'],
            'address_en' => $this->item['address_en'],
        ]);
        $model->save();

        if ($isNew) {
            foreach (\App\Models\User::all() as $user) {
                $user->notify(new \App\Notifications\NewCenterCreated($model));
            }
            $this->dispatch('center-created');
        }

        $this->showModal = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('closeModal');

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
