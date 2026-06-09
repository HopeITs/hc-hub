<?php

namespace App\Livewire\Basics;

use App\Models\TerminationReason;
use Livewire\Component;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class TerminationReasonModule extends Component
{
    protected $listeners = ['openTerminationReasonEdit' => 'edit'];

    public bool $showModal = false;
    public string $modalTitle = '';

    public array $item = [
        'id' => null,
        'title_en' => null,
        'title_ar' => null,
    ];

    protected function rules(): array
    {
        return [
            'item.title_en' => 'required|string|max:255',
            'item.title_ar' => 'required|string|max:255',
        ];
    }

    public function render()
    {
        return view('livewire.basics.termination-reason-module');
    }

    public function create(): void
    {
        $this->item = [
            'id' => null,
            'title_en' => null,
            'title_ar' => null,
        ];
        $this->resetValidation();
        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $model = TerminationReason::findOrFail($id);

        $this->item = [
            'id' => $model->id,
            'title_en' => $model->title_en,
            'title_ar' => $model->title_ar,
        ];

        $this->modalTitle = __('general.edit');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $model = !empty($this->item['id'])
            ? TerminationReason::findOrFail($this->item['id'])
            : new TerminationReason();

        $model->fill($this->item)->save();

        $this->showModal = false;

        $this->dispatch('refreshTerminationReasonTable');

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
