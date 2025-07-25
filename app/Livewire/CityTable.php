<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\City;
use Livewire\WithPagination;

class CityTable extends Component
{
    use WithPagination;

    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    public function sortBy(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $cities = City::orderBy($this->sortField, $this->sortDirection)->paginate(10);

        return view('livewire.city-table', [
            'cities' => $cities,
        ]);
    }
}
