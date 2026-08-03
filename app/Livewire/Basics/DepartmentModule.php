<?php

namespace App\Livewire\Basics;

use App\Livewire\Concerns\AuthorizesHrAction;
use App\Models\Department;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class DepartmentModule extends Component
{
    use AuthorizesHrAction;

    protected $listeners = [
        'openDepartmentEdit' => 'edit',
    ];

    public bool $showModal = false;

    public string $modalTitle = '';

    public array $item = [
        'id' => null,
        'name' => '',
        'code' => '',
        'description_ar' => '',
        'description_en' => '',
        'email' => null,
    ];

    protected function rules(): array
    {
        return [
            'item.name' => 'required|string|max:255',

            'item.code' => [
                'required',
                'string',
                'max:5',
                Rule::unique('departments', 'code')
                    ->ignore($this->item['id'] ?? null),
            ],

            'item.description_ar' => 'nullable|string',
            'item.description_en' => 'nullable|string',
            'item.email' => 'nullable|email|max:255',
        ];
    }

    public function render()
    {
        return view('livewire.basics.department-module');
    }

    public function create(): void
    {
        $this->item = [
            'id' => null,
            'name' => '',
            'code' => '',
            'description_ar' => '',
            'description_en' => '',
            'email' => null,
        ];

        $this->resetValidation();

        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $model = Department::findOrFail($id);

        $this->item = [
            'id' => $model->id,
            'name' => $model->name,
            'code' => $model->code,
            'description_ar' => $model->description_ar,
            'description_en' => $model->description_en,
            'email' => $model->email,
        ];

        $this->modalTitle = __('general.edit');

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorizeHrAction();

        $this->validate();

        $model = ! empty($this->item['id'])
            ? Department::findOrFail($this->item['id'])
            : new Department;

        $model->fill($this->item)->save();

        $this->showModal = false;

        $this->dispatch('refreshDepartmentTable');

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }
}
