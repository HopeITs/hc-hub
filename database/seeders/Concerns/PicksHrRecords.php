<?php

namespace Database\Seeders\Concerns;

use Database\Factories\Concerns\CreatesHrRecords;
use Illuminate\Support\Collection;
use Modules\Core\Models\City;
use Modules\Hr\Models\Employee;

/**
 * Hands seeders the ids of real HR cities and employees to build demo data on.
 *
 * Seeders never create HR data. An empty HR table only happens in the test
 * suite, whose in-memory HR database accepts a few inserted rows; against the
 * real hr_db the READ ONLY connection would reject them.
 */
trait PicksHrRecords
{
    use CreatesHrRecords;

    /**
     * The ids of the HR cities projects can be placed in.
     *
     * @return Collection<int, int>
     */
    protected function hrCityIds(): Collection
    {
        $ids = City::query()->pluck('id');

        return $ids->isNotEmpty() ? $ids : collect(range(1, 3))->map(fn (): int => $this->createHrCity());
    }

    /**
     * The ids of the active HR employees work can be assigned to.
     *
     * @return Collection<int, int>
     */
    protected function hrEmployeeIds(): Collection
    {
        $ids = Employee::query()->where('is_active', true)->pluck('id');

        return $ids->isNotEmpty() ? $ids : collect(range(1, 6))->map(fn (): int => $this->createHrEmployee());
    }
}
