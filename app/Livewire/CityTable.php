<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Models\City;
use Livewire\WithPagination;

class CityTable extends Component
{
    use WithPagination;


    #[On('toggleCityStatus')]
    public function toggleCityStatus(int $id)
    {
        $city = City::findOrFail($id);
        $city->is_active = !$city->is_active;
        $city->save();

        session()->flash('message', __('general.status-updated'));
    }

    public function render()
    {
        $cities = City::paginate(20);

        return view('livewire.city-table', [
            'cities' => $cities,
        ]);
    }
}
