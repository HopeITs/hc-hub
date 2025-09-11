<?php

namespace App\Livewire\Basics;

use App\Models\Department;
use App\Traits\SortableTable;
use Livewire\Component;
use Livewire\WithPagination;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class DepartmentModule extends Component
{

    use WithPagination, SortableTable;

    public $showModal = false;
    public $modalTitle = '';
    public $search = '';
    public $item = [
        'name' => [],
        'description' => [],
        'email' => null,
    ];

    protected $rules = [
        'item.name.*' => 'required|string|max:255',
        'item.description.*' => 'nullable|string',
        'item.email' => 'nullable|email|max:255',
    ];

    public function render()
    {
        $locale = app()->getLocale();
        $sort   = $this->sortField ?: 'id';
        $dir    = $this->sortDirection ?: 'asc';

        $query = \App\Models\Department::query()
            ->when($this->search, function ($q) {
                $term = $this->search;

                // Search only in name column
                $q->search(['name'], $term);
            });

        // Sorting
        if ($sort === 'name') {
            $query->orderBy('name', $dir);
        } elseif ($sort === 'description') {
            $query->orderBy('description', $dir);
        } elseif ($sort === 'email') {
            $query->orderByRaw("COALESCE(email, '-') {$dir}");
        } else {
            $query->orderBy($sort, $dir);
        }

        $departments = $query->paginate(15);

        return view('livewire.basics.department-module', compact('departments'));
    }

    public function create()
    {
        $this->item = [
            'name' => ['en' => ''],
            'description' => [],
            'email' => null,
        ];

        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    public function edit($id)
    {
        $model = Department::findOrFail($id);

        $this->item = [
            'id' => $model->id,
            'name' => ['en' => $model->name], // ✅ FIXED
            'description' => $model->getTranslations('description'),
            'email' => $model->email,
        ];

        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }


    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->item['name']['en'] ?? '',
            // Extract English name only
            'description' => $this->item['description'], // still array (translatable)
            'email' => $this->item['email'],
        ];

        if (isset($this->item['id'])) {
            Department::findOrFail($this->item['id'])->update($data);
        } else {
            Department::create($data);
        }

        $this->reset(['item', 'showModal']);
        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
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

    public function delete($payload)
    {
        Department::findOrFail($payload['id'])->delete();
        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

    }


}
