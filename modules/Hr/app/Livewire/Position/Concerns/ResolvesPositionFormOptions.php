<?php

namespace Modules\Hr\Livewire\Position\Concerns;

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
        $activeCityIds = auth()->user()?->activeCityIds();

        $query = Center::query()
            ->where('is_active', true)
            ->with('city');

        if ($activeCityIds !== null) {
            // A role covering several cities narrows to the one picked in the
            // form, as long as it is one of its own.
            $centerCityIds = $cityId && in_array($cityId, $activeCityIds, true) ? [$cityId] : $activeCityIds;

            $query->where(fn ($query) => $query
                ->whereIn('city_id', $centerCityIds)
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
