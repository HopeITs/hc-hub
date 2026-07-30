<?php

namespace App\Livewire\Basics;

use App\Livewire\Concerns\AuthorizesActivePermissions;
use App\Models\ChecklistItem;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class ChecklistItemModule extends Component
{
    use AuthorizesActivePermissions;

    protected $listeners = [
        'openChecklistItemEdit' => 'edit',
    ];

    public bool $showModal = false;

    public string $modalTitle = '';

    public array $item = [
        'id' => null,
        'name' => '',
        'order' => null,
    ];

    protected function rules(): array
    {
        return [
            'item.name' => 'required|string',
            'item.order' => 'required|integer',
        ];
    }

    public function render()
    {
        return view('livewire.basics.checklist-item-module');
    }

    public function create(): void
    {
        $this->authorizeActivePermission('project-basics.create');
        $this->item = [
            'id' => null,
            'name' => '',
            'order' => null,
        ];
        $this->resetValidation();

        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeActivePermission('project-basics.update');
        $model = ChecklistItem::findOrFail($id);

        $this->item = [
            'id' => $model->id,
            'name' => $model->name,
            'order' => $model->order,
        ];

        $this->modalTitle = __('general.edit');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorizeActivePermission(
            empty($this->item['id']) ? 'project-basics.create' : 'project-basics.update'
        );
        $this->validate();

        $model = ! empty($this->item['id'])
        // $model = $this->item['id']
            ? ChecklistItem::findOrFail($this->item['id'])
            : new ChecklistItem;

        $model->fill($this->item)->save();

        $this->showModal = false;

        $this->dispatch('refreshChecklistItemTable');

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }
}
