<?php

namespace App\Livewire\Contract\Concerns;

use App\Livewire\Forms\ContractForm;
use App\Models\City;
use App\Models\ContractType;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Renderless;

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
    /**
     * Return the complete, compact option graph for one authorized city.
     *
     * This method is intentionally renderless: changing a city performs one
     * small JSON request without rebuilding the contract form or its Select2
     * instances. Program, center, and position filtering then happens locally.
     */
    #[Renderless]
    public function locationOptions(int $cityId): array
    {
        $user = auth()->user();

        abort_unless($user, 403);
        $user->loadMissing(['profile:id,city_id', 'roles']);

        abort_unless($user->hasActivePermission($this->locationOptionsPermission()), 403);

        $activeCityId = $user->activeCityId();

        abort_unless(
            City::query()
                ->whereKey($cityId)
                ->where('is_active', true)
                ->when($activeCityId, fn ($query, int $allowedCityId) => $query->whereKey($allowedCityId))
                ->exists(),
            403,
        );

        $locale = app()->getLocale();
        $positionTitleColumn = "title_{$locale}";
        $centerNameColumn = "name_{$locale}";
        $ensureEmployeeId = $this->locationOptionsEmployeeId();

        $positions = Position::query()
            ->inCity($cityId)
            ->with([
                'program:id,name',
                'center' => fn ($query) => $query->select('id', 'city_id', $centerNameColumn),
            ])
            ->select('id', 'program_id', 'center_id', 'city_id', $positionTitleColumn)
            ->orderBy($positionTitleColumn)
            ->get();

        $employees = $this->employeeOptionsForCity($cityId, $locale, $user);

        if ($ensureEmployeeId && ! collect($employees)->contains('value', $ensureEmployeeId)) {
            $currentEmployee = Employee::query()
                ->select('id', 'city_id', 'first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar')
                ->find($ensureEmployeeId);

            if ($currentEmployee) {
                $employees[] = [
                    'value' => $currentEmployee->id,
                    'label' => $currentEmployee->nameForLocale($locale),
                ];
            }
        }

        return [
            'employees' => collect($employees)
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
            'programs' => $positions
                ->filter(fn (Position $position) => $position->program !== null)
                ->unique('program_id')
                ->map(fn (Position $position) => [
                    'value' => $position->program_id,
                    'label' => $position->program->name,
                ])
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
            'centers' => $positions
                ->filter(fn (Position $position) => $position->center !== null)
                ->groupBy('center_id')
                ->map(function (Collection $centerPositions) use ($centerNameColumn): array {
                    /** @var Position $first */
                    $first = $centerPositions->first();

                    return [
                        'value' => $first->center_id,
                        'label' => $first->center->{$centerNameColumn},
                        'programIds' => $centerPositions->pluck('program_id')->unique()->values()->all(),
                    ];
                })
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
            'positions' => $positions
                ->map(fn (Position $position) => [
                    'value' => $position->id,
                    'label' => $position->{$positionTitleColumn},
                    'programId' => $position->program_id,
                    'centerId' => $position->center_id,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Apply the locally selected identity graph as one atomic server update.
     */
    protected function applyIdentitySelection(array $selection): void
    {
        $previousContractTypeId = $this->form->contract_type_id;

        $this->city_id = $this->selectionId($selection, 'cityId');
        $this->form->employee_id = $this->selectionId($selection, 'employeeId');
        $this->program_id = $this->selectionId($selection, 'programId');
        $this->center_id = $this->selectionId($selection, 'centerId');
        $this->form->position_id = $this->selectionId($selection, 'positionId');
        $this->form->contract_type_id = $this->selectionId($selection, 'contractTypeId');

        if ($this->form->contract_type_id && $this->form->contract_type_id !== $previousContractTypeId) {
            $this->form->updatedContractTypeId($this->form->contract_type_id);
        }
    }

    abstract protected function locationOptionsPermission(): string;

    protected function locationOptionsEmployeeId(): ?int
    {
        return null;
    }

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

    private function getContractTypes(string $col): Collection
    {
        return ContractType::select('id', $col)
            ->orderBy($col)
            ->get()
            ->each(fn (ContractType $t) => $t->name = $t->{$col});
    }

    /**
     * Hydrate only the six columns needed to build employee dropdown labels.
     */
    private function employeeOptionsForCity(int $cityId, string $locale, User $user): array
    {
        [$roleType] = array_pad(explode('-', (string) $user->myRole(), 2), 2, null);

        if ($roleType !== 'HR' && (int) $user->profile?->city_id !== $cityId) {
            return [];
        }

        return Employee::query()
            ->select('id', 'city_id', 'first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar')
            ->where('is_active', true)
            ->where('city_id', $cityId)
            ->orderBy("first_name_{$locale}")
            ->orderBy("last_name_{$locale}")
            ->get()
            ->map(fn (Employee $employee) => [
                'value' => $employee->id,
                'label' => $employee->nameForLocale($locale),
            ])
            ->all();
    }

    private function selectionId(array $selection, string $key): ?int
    {
        $value = filter_var($selection[$key] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return $value === false ? null : $value;
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
