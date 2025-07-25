<?php

namespace App\Livewire;

use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\City;
use Livewire\WithPagination;

class CityTable extends Component
{
    use WithPagination;


    public $cityId;

    public function confirmToggleCityStatus($id)
    {
        $this->cityId = $id;

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->asConfirm()
            ->confirmButtonColor('red')
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

        LivewireAlert::title(__('general.status-updated'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();
    }

    public function render()
    {
        $cities = City::paginate(20);

        return view('livewire.city-table', [
            'cities' => $cities,
        ]);
    }
}
