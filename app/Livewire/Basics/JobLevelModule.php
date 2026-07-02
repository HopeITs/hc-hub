<?php

namespace App\Livewire\Basics;

use App\Livewire\Concerns\AuthorizesHrAction;
use App\Models\JobLevel;
use Illuminate\Validation\Rule;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class JobLevelModule extends Component
{
    use AuthorizesHrAction;

    protected $listeners = [
        'openJobLevelEdit' => 'edit',
    ];

    public bool $showModal = false;

    public string $modalTitle = '';

    public array $item = [
        'id' => null,
        'name_ar' => '',
        'name_en' => '',
        'description' => null,
        'grade' => null,
        'email' => null,
    ];

    protected function rules(): array
    {
        return [
            'item.name_ar' => 'required|string|max:255',
            'item.name_en' => 'required|string|max:255',
            'item.description' => 'nullable|string',
            'item.grade' => 'nullable|integer',
            'item.email' => [
                'nullable',
                'email',
                Rule::unique('job_levels', 'email')->ignore($this->item['id']),
            ],
        ];
    }

    public function render()
    {
        return view('livewire.basics.job-level');
    }

    public function create(): void
    {
        $this->item = [
            'id' => null,
            'name_ar' => '',
            'name_en' => '',
            'description' => null,
            'grade' => null,
            'email' => null,
        ];

        $this->resetValidation();

        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $model = JobLevel::findOrFail($id);

        $this->item = [
            'id' => $model->id,
            'name_ar' => $model->name_ar,
            'name_en' => $model->name_en,
            'description' => $model->description,
            'grade' => $model->grade,
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
            ? JobLevel::findOrFail($this->item['id'])
            : new JobLevel;

        $model->fill($this->item)->save();

        $this->showModal = false;

        $this->dispatch('refreshJobLevelTable');

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }
}
