<?php

namespace App\Livewire\Contract\Concerns;

use App\Livewire\Forms\ContractForm;
use App\Models\Center;
use App\Models\City;
use App\Models\ContractType;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Program;
use Illuminate\Support\Collection;

/**
 * Shared dropdown queries and position/city consistency checks for
 * ContractCreate and ContractEdit.
 *
 * @property ContractForm $form
 * @property ?int $city_id
 * @property ?int $program_id
 * @property ?int $center_id
 */
trait ResolvesContractLocationOptions
{
    private function getCities(string $col): Collection
    {
        return City::select('id', $col)
            ->where('is_active', true)
            ->when(
                auth()->user()?->activeCityId(),
                fn ($query, int $cityId) => $query->whereKey($cityId),
            )
            ->get()
            ->each(fn (City $city) => $city->name = $city->{$col});
    }

    private function getPrograms(): Collection
    {
        if (! $this->city_id) {
            return collect();
        }

        $programIds = Position::query()
            ->inCity($this->city_id)
            ->pluck('program_id')
            ->unique();

        return Program::whereIn('id', $programIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    private function getCenters(): Collection
    {
        if (! $this->city_id || ! $this->program_id) {
            return collect();
        }

        $nameColumn = transColumn('name');

        return Center::select('centers.id', "centers.{$nameColumn}")
            ->whereHas('positions', function ($q) {
                $q->where('program_id', $this->program_id)
                    ->inCity($this->city_id);
            })
            ->orderBy("centers.{$nameColumn}")
            ->get();
    }

    private function getPositions(string $col, ?int $ensurePositionId = null): Collection
    {
        if (! $this->city_id || ! $this->program_id) {
            return collect();
        }

        $positions = Position::where('program_id', $this->program_id)
            ->inCity($this->city_id)
            ->when(
                $this->center_id,
                fn ($q) => $q->where('center_id', $this->center_id),
                fn ($q) => $q->whereNull('center_id'),
            )
            ->select('id', $col, 'center_id', 'city_id')
            ->orderBy($col)
            ->get()
            ->each(fn (Position $p) => $p->title = $p->{$col});

        if ($ensurePositionId && $positions->doesntContain('id', $ensurePositionId)) {
            $current = Position::select('id', $col)->find($ensurePositionId);

            if ($current) {
                $current->title = $current->{$col};
                $positions->push($current);
            }
        }

        return $positions;
    }

    private function getContractTypes(string $col): Collection
    {
        return ContractType::select('id', $col)
            ->orderBy($col)
            ->get()
            ->each(fn (ContractType $t) => $t->name = $t->{$col});
    }

    /**
     * Employees selectable for the contract, scoped by the current user's role.
     */
    private function resolveEmployees(string $orderCol, bool $requireCity = true): Collection
    {
        if ($requireCity && ! $this->city_id) {
            return collect();
        }

        $user = auth()->user()->load('profile');
        [$roleType, $roleScope] = array_pad(explode('-', $user->myRole(), 2), 2, null);

        if ($roleType !== 'HR') {
            $cityId = optional($user->profile)->city_id;
            abort_unless($cityId, 403, 'User has no linked city.');

            return Employee::where('is_active', true)
                ->where('city_id', $cityId)
                ->when($this->city_id, fn ($query) => $query->where('city_id', $this->city_id))
                ->orderBy($orderCol)
                ->get();
        }

        $query = Employee::where('is_active', true)->orderBy($orderCol);

        if ($roleScope !== 'SYR') {
            $query->whereHas('city', fn ($q) => $q->where('slug', $roleScope));
        }

        if ($this->city_id) {
            $query->where('city_id', $this->city_id);
        }

        return $query->get();
    }

    private function ensureEmployeeInList(Collection $employees, ?int $employeeId): Collection
    {
        if ($employeeId && $employees->doesntContain('id', $employeeId)) {
            $current = Employee::find($employeeId);

            if ($current) {
                $employees = $employees->push($current)->unique('id')->values();
            }
        }

        return $employees;
    }

    private function validateCityConsistency(): bool
    {
        $activeCityId = auth()->user()?->activeCityId();
        if ($activeCityId !== null && $activeCityId !== (int) $this->city_id) {
            $this->addError('city_id', __('You may only manage contracts in your active city.'));

            return false;
        }

        if (! Employee::query()
            ->whereKey($this->form->employee_id)
            ->where('city_id', $this->city_id)
            ->exists()) {
            $this->addError('form.employee_id', __('The selected employee must belong to the contract city.'));

            return false;
        }

        if (! $this->city_id || ! $this->form->position_id) {
            return false;
        }

        $position = Position::select('id', 'program_id', 'center_id', 'city_id')
            ->find($this->form->position_id);

        if (! $position) {
            $this->addError('form.position_id', __('validation.exists', ['attribute' => 'position']));

            return false;
        }

        $belongsToCity = Position::whereKey($position->id)
            ->inCity($this->city_id)
            ->exists();

        if (! $belongsToCity) {
            $this->addError('form.position_id', __('validation.custom.position_id.invalid'));

            return false;
        }

        if ($this->program_id && (int) $position->program_id !== (int) $this->program_id) {
            $this->addError('form.position_id', __('validation.custom.position_id.program_mismatch'));

            return false;
        }

        if ($this->center_id && (int) $position->center_id !== (int) $this->center_id) {
            $this->addError('form.position_id', __('validation.custom.position_id.center_mismatch'));

            return false;
        }

        return true;
    }
}
