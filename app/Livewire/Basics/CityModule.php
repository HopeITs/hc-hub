<?php

namespace App\Livewire\Basics;

use App\Models\City;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CityModule extends Component
{
    use WithPagination;


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
        $cities = City::paginate(15);

        return view('livewire.basics.city-module', [
            'cities' => $cities,
        ]);
    }
}
