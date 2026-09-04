<?php

namespace App\Livewire\Position\Concerns;

use App\Models\Center;
use App\Models\City;
use Illuminate\Support\Collection;

trait ResolvesPositionFormOptions
{
    /**
     * City drives the physical location choices. Reset the dependent fields so
     * a stale center id is not submitted after the option set changes.
     */
    public function updatedFormCityId($cityId): void
    {
        $this->form->city_id = $this->validPositionFormCityId($cityId);
        $this->form->center_id = null;
    }

    /**
     * @return Collection<int, Center>
     */
    protected function positionFormCenters(?int $cityId = null): Collection
    {
        $cityId = $this->validPositionFormCityId($cityId ?? $this->form->city_id);
        $activeCityId = auth()->user()?->activeCityId();

        $query = Center::query()
            ->where('is_active', true)
            ->with('city');

        if ($activeCityId) {
            $query->where(fn ($query) => $query
                ->where('city_id', $activeCityId)
                ->orWhereNull('city_id'));
        } elseif ($cityId && ! City::isNational($cityId)) {
            $query->where(fn ($query) => $query
                ->where('city_id', $cityId)
                ->orWhereNull('city_id'));
        } elseif (! $cityId) {
            $query->whereNull('city_id');
        }

        return $query->orderBy(transColumn('name'))->get();
    }

    private function validPositionFormCityId(mixed $cityId): ?int
    {
        $cityId = filter_var($cityId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $cityId === false ? null : $cityId;
    }
}
