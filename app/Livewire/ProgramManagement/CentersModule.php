<?php

namespace App\Livewire\ProgramManagement;

use App\Models\Center;
use App\Models\City;
use App\Models\Program;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class CentersModule extends Component
{
    // Listen for edit requests coming from the table
    protected $listeners = [
        'openCenterEdit' => 'edit',
    ];

    public bool $showModal = false;
    public string $modalTitle = '';
    public array $item = [
        'id'         => null,
        'name'       => '',
        'city_id'    => '',
        'program_id' => '',
        'address'    => ['en' => '', 'ar' => ''],
        'is_active'  => true,
    ];

    protected $rules = [
        'item.name'       => 'required|string|max:255',
        'item.city_id'    => 'required|exists:cities,id',
        'item.program_id' => 'required|exists:programs,id',
        'item.address.en' => 'nullable|string|max:255',
        'item.address.ar' => 'nullable|string|max:255',
    ];

    public function render()
    {
        $locale   = app()->getLocale();
        $cities   = City::orderBy("name->$locale")->get(['id', 'name']);
        $programs = Program::orderBy('name')->get(['id', 'name']);

        // The grid is now a Livewire Tables child; we only pass data for the modal
        return view('livewire.program-management.centers-module', compact('cities', 'programs'));
    }

    public function create(): void
    {
        $this->item = [
            'id'         => null,
            'name'       => '',
            'city_id'    => '',
            'program_id' => '',
            'address'    => ['en' => '', 'ar' => ''],
            'is_active'  => true,
        ];

        $this->resetValidation();
        $this->modalTitle = __('general.add-new');
        $this->showModal  = true;
    }

    public function edit(int $id): void
    {
        $model = Center::findOrFail($id);

        $this->item = [
            'id'         => $model->id,
            'name'       => $model->name,
            'city_id'    => $model->city_id,
            'program_id' => $model->program_id,
            'address'    => array_merge(['en' => '', 'ar' => ''], $model->getTranslations('address')),
            'is_active'  => (bool) $model->is_active,
        ];

        $this->resetValidation();
        $this->modalTitle = __('general.edit');
        $this->showModal  = true;
    }

    public function save(): void
    {
        $this->validate();

        $model = Center::findOrNew($this->item['id']);
        $model->fill([
            'name'       => $this->item['name'],
            'city_id'    => $this->item['city_id'],
            'program_id' => $this->item['program_id'],
        ]);
        $model->setTranslations('address', $this->item['address']);
        $model->save();

        $this->showModal = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('closeModal');

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
