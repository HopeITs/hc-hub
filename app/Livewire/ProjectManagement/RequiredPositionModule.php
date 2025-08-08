<?php

namespace App\Livewire\ProjectManagement;

use App\Models\Project;
use App\Models\RequiredPosition;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\SortableTable;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class RequiredPositionModule extends Component
{
    use WithPagination, SortableTable;

    public $search = '';
    public $showModal = false;
    public $modalTitle = '';
    public $itemID;

    public $item = [
        'title' => '',
        'project_id' => '',
        'is_active' => true,
        'qualifications' => '',
    ];

    protected $rules = [
        'item.title' => 'required|string|max:255',
        'item.project_id' => 'required|exists:projects,id',
        'item.qualifications' => 'nullable|string',
    ];

    public function confirmToggleItemStatus($id)
    {
        $this->itemID = $id;

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.confirm'))
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $this->itemID])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed()
    {
        $item = RequiredPosition::findOrFail($this->itemID);
        $item->is_active = !$item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function render()
    {
        $locale = app()->getLocale();
        $sort   = $this->sortField ?: 'id';
        $dir    = $this->sortDirection ?: 'desc';

        $query = RequiredPosition::query()
            ->with('project')
            ->when($this->search, function ($q) use ($locale) {
                $term = $this->search;

                $q->where(function ($w) use ($term, $locale) {
                    // Own columns
                    $w->search(['title', 'qualifications'], $term)

                        // Project name (translatable JSON)
                        ->orWhereHas('project', function ($p) use ($term, $locale) {
                            $p->search(["name->$locale"], $term);
                        });
                });
            });

        // Sorting (supports: id, title, is_active, project_name)
        if ($sort === 'title') {
            $query->orderBy('title', $dir);
        } elseif ($sort === 'project_name') {
            $query->leftJoin('projects', 'required_positions.project_id', '=', 'projects.id')
                ->orderBy("projects.name->$locale", $dir)
                ->select('required_positions.*');
        } elseif ($sort === 'is_active') {
            $query->orderBy('is_active', $dir);
        } else {
            // id or any other simple column
            $query->orderBy($sort, $dir);
        }

        $items = $query->paginate(15);

        return view('livewire.project-management.required-position-module', compact('items'));
    }

    public function create()
    {
        $this->reset('item');
        $this->resetValidation();
        $this->showModal = true;
        $this->modalTitle = __('general.add_new');
    }

    public function edit($id)
    {
        $model = RequiredPosition::findOrFail($id);

        $this->modalTitle = __('general.edit');
        $this->item = $model->only(['id', 'title', 'project_id', 'qualifications']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if (array_key_exists('id', $this->item)) {
            RequiredPosition::findOrFail($this->item['id'])->update($this->item);
        } else {
            RequiredPosition::create([
                'title' => $this->item['title'],
                'project_id' => $this->item['project_id'],
                'qualifications' => $this->item['qualifications'],
            ]);
        }

        $this->reset('item', 'showModal');

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
        RequiredPosition::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }
}
