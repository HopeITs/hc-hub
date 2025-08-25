<?php

namespace App\Livewire\ProjectManagement;

use App\Models\Center;
use App\Models\City;
use App\Models\Project;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\SortableTable;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class CentersModule extends Component
{
    use WithPagination, SortableTable;

    public $search = '';
    public $showModal = false;
    public $modalTitle = '';
    public $itemID;

    public $item = [
        'id'         => null,
        'name'       => '',
        'city_id'    => '',
        'project_id' => '',
        'address'    => ['en' => '', 'ar' => ''],
        'is_active'  => true,
    ];

    protected $rules = [
        'item.name'       => 'required|string|max:255',
        'item.city_id'    => 'required|exists:cities,id',
        'item.project_id' => 'required|exists:projects,id',
        'item.address.en' => 'nullable|string|max:255',
        'item.address.ar' => 'nullable|string|max:255',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmToggleItemStatus($id)
    {
        $this->itemID = $id;

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.confirm'))
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed()
    {
        $item = Center::findOrFail($this->itemID);
        $item->is_active = !$item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()->toast()->position('top-start')->show();
    }

    public function render()
    {
        $locale = app()->getLocale();
        $sort = $this->sortField ?: 'id';
        $dir = $this->sortDirection ?: 'desc';

        $query = Center::query()
            ->with(['city', 'project'])
            ->when($this->search, function ($q) use ($locale) {
                $term = $this->search;
                $q->where(function ($w) use ($term, $locale) {
                    $w->where('name', 'like', "%{$term}%")
                      ->orWhere("address->$locale", 'like', "%{$term}%")
                      ->orWhereHas('city', function ($c) use ($term, $locale) {
                          $c->where("name->$locale", 'like', "%{$term}%");
                      })
                      ->orWhereHas('project', function ($p) use ($term) {
                          $p->where('name', 'like', "%{$term}%");
                      });
                });
            });

        if ($sort === 'address') {
            $query->orderBy("address->$locale", $dir);
        } elseif ($sort === 'city_name') {
            $query->leftJoin('cities', 'centers.city_id', '=', 'cities.id')
                  ->orderBy("cities.name->$locale", $dir)
                  ->select('centers.*');
        } elseif ($sort === 'project_name') {
            $query->leftJoin('projects', 'centers.project_id', '=', 'projects.id')
                  ->orderBy('projects.name', $dir)
                  ->select('centers.*');
        } else {
            $query->orderBy($sort, $dir);
        }

        $items = $query->paginate(15);

        $cities = City::orderBy("name->$locale")->get(['id', 'name']);
        $projects = Project::orderBy('name')->get(['id', 'name']);

        return view('livewire.project-management.centers-module', compact('items', 'cities', 'projects'));
    }

    public function create()
    {
        $this->item = [
            'id'         => null,
            'name'       => '',
            'city_id'    => '',
            'project_id' => '',
            'address'    => ['en' => '', 'ar' => ''],
            'is_active'  => true,
        ];

        $this->resetValidation();
        $this->showModal = true;
        $this->modalTitle = __('general.add_new');
    }

    public function edit($id)
    {
        $model = Center::findOrFail($id);

        $this->modalTitle = __('general.edit');
        $this->item = [
            'id'         => $model->id,
            'name'       => $model->name,
            'city_id'    => $model->city_id,
            'project_id' => $model->project_id,
            'address'    => array_merge(['en' => '', 'ar' => ''], $model->getTranslations('address')),
            'is_active'  => (bool) $model->is_active,
        ];

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->item['name'],
            'city_id' => $this->item['city_id'],
            'project_id' => $this->item['project_id'],
        ];

        $model = Center::findOrNew($this->item['id']);
        $model->fill($data);
        $model->setTranslations('address', $this->item['address']);
        $model->save();

        $this->showModal = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('closeModal');

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
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
        Center::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
