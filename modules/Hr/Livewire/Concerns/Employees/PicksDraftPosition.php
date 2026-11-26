<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Employees;

use Livewire\Attributes\Computed;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * The city, program and position pickers that hold a position for a new
 * employee with a draft contract (ReservePosition). Used by the profile's
 * "Reserve a position" and by the step after a new record's first save.
 */
trait PicksDraftPosition
{
    public string $draftCityId = '';

    public string $draftProgramId = '';

    public string $draftPositionId = '';

    public function updatedDraftCityId(): void
    {
        $this->draftProgramId = '';
        $this->draftPositionId = '';
    }

    public function updatedDraftProgramId(): void
    {
        $this->draftPositionId = '';
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function draftCityOptions(): array
    {
        $allowed = HrAccess::cityIds(auth()->user());

        return array_filter(City::options(), fn (int $id): bool => $allowed === null || in_array($id, $allowed, true), ARRAY_FILTER_USE_KEY);
    }

    /**
     * The programs that have an open position in the chosen city.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function draftProgramOptions(): array
    {
        if ($this->draftCityId === '') {
            return [];
        }

        $programIds = Position::query()->inCity((int) $this->draftCityId)->visible()->distinct()->pluck('program_id')->all();

        return array_intersect_key(Program::namesById(), array_flip($programIds));
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function draftPositionOptions(): array
    {
        if ($this->draftCityId === '' || $this->draftProgramId === '') {
            return [];
        }

        return Position::query()
            ->inCity((int) $this->draftCityId)
            ->where('program_id', (int) $this->draftProgramId)
            ->visible()
            ->orderBy(Position::translatedColumn('title'))
            ->get(['id', 'city_id', 'title_en', 'title_ar'])
            ->mapWithKeys(fn (Position $position): array => [$position->id => (string) $position->title])
            ->all();
    }
}
