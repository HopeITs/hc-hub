<?php

namespace App\Livewire\Basics;

use App\Models\City;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\SortableTable;

class CityModule extends Component
{
    use WithPagination, SortableTable;

    public $search = '';
    public $itemID;

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
        $item = City::findOrFail($this->itemID);
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
        $sort   = $this->sortField ?: 'is_active';
        $dir    = $this->sortDirection ?: 'asc';

        $query = City::query()
            ->when($this->search, function ($q)  {
                $term = $this->search;
                $q->where(function ($w) use ($term) {
                    // search() is your app macro used elsewhere
                    $w->search(['name_ar', 'name_en', 'code', 'slug'], $term);
                });
            });

        // Sorting (match your other modules)
        switch ($sort) {
            case 'name_ar':
                $query->orderBy("name_ar", $dir);
                break;
            case 'name_en':
                $query->orderBy("name_en", $dir);
                break;

            case 'code':
            case 'slug':
            case 'is_active':
                $query->orderBy($sort, $dir);
                break;
            default: // id
                $query->orderBy('is_active', 'desc');
        }

        $cities = $query->paginate(16);

        return view('livewire.basics.city-module', compact('cities'));
    }
}
