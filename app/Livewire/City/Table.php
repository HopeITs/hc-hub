<?php

namespace App\Livewire\City;

use App\Models\City;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;


    public $cityId;

    public function confirmToggleCityStatus($id)
    {
        $this->cityId = $id;

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.confirm'))
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleCityStatusConfirmed', ['id' => $this->cityId])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }


    public function toggleCityStatusConfirmed()
    {
        $city = City::findOrFail($this->cityId);
        $city->is_active = !$city->is_active;
        $city->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function render()
    {
        $cities = City::paginate(10);

        return view('livewire.city.table', [
            'cities' => $cities,
        ]);
    }
}
