<?php

namespace App\Livewire\Basics;

use App\Models\JobLevel;
use App\Traits\SortableTable;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class JobLevelModule extends Component
{
    use WithPagination, SortableTable;

    public $showModal = false;
    public $modalTitle = 'Modal title';
    public $search = '';

    public $item = [
        'id' => null,
        'name_ar' => '',
        'name_en' => '',
        'description' => null,
        'grade' => null,
        'email' => null,
    ];

    protected function rules()
    {
        return [
            'item.name_ar' => 'required|string|max:255',
            'item.name_en' => 'required|string|max:255',
            'item.description' => 'nullable|string',
            'item.grade' => 'nullable|integer',
            'item.email' => ['nullable', 'email', Rule::unique('job_levels', 'email')->ignore($this->item['id'])],
        ];
    }

    public function create()
    {
        $this->item = [
            'id' => null,
            'name_ar' => '',
            'name_en' => '',

            'description' => null,
            'grade' => null,
            'email' => null,
        ];
        $this->resetErrorBag();
        $this->resetValidation();
        $this->showModal = true;
        $this->modalTitle = __('general.add-new');
    }

    public function edit($id)
    {
        $model = JobLevel::findOrFail($id);
        $this->modalTitle = __('general.edit');
        $this->item = [
            'id' => $model->id,
            'name_ar' => $model->name_ar,
            'name_en' => $model->name_en,
            'description' => $model->description,
            'grade' => $model->grade,
            'email' => $model->email,
        ];
        $this->resetErrorBag();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name_ar' => $this->item['name_ar'],
            'name_en' => $this->item['name_en'],
            'description' => $this->item['description'],
            'grade' => $this->item['grade'],
            'email' => $this->item['email'],
        ];

        if (!empty($this->item['id'])) {
            JobLevel::findOrFail($this->item['id'])->update($data);
        } else {
            JobLevel::create($data);
        }

        $this->sortField = 'grade';
        $this->sortDirection = 'asc';
        $this->showModal = false;
        $this->reset('item');

        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();
    }

    public function confirmDelete($id)
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.primary'))
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete($id)
    {
        JobLevel::find($id['id'])->delete();
        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();
    }

    public function render()
    {
        $sort = $this->sortField ?: 'id';
        $dir = $this->sortDirection ?: 'asc';

        $query = JobLevel::query()->when($this->search, function ($q) {
            $term = $this->search;

            $q->where(function ($w) use ($term) {
                // Reuse your app-wide search() scope like other modules
                $w->search(['name_en', 'name_ar', 'description', 'email'], $term);
            });
        });

        // Sorting (keep keys in sync with Blade headers)
        switch ($sort) {
            case 'name_ar':
                $query->orderBy('name_ar', $dir);
                break;

            case 'name_en':
                $query->orderBy('name_en', $dir);
                break;

            case 'grade':
            case 'email':
                $query->orderBy($sort, $dir);
                break;

            default:
                // id
                $query->orderBy('id', $dir);
        }

        $items = $query->paginate(15);

        return view('livewire.basics.job-level', compact('items'));
    }
}
