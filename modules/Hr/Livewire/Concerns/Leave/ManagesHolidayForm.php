<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Leave;

use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Leave\SaveHoliday;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Models\HolidayProject;
use Modules\Hr\Support\Leave\HolidayProjects;

/**
 * The add/edit holiday dialog, shared by the holidays page and the team
 * calendar (which opens it on a day). The project picker and the rules both
 * come from HolidayProjects; the write goes through SaveHoliday.
 */
trait ManagesHolidayForm
{
    public bool $showHolidayModal = false;

    #[Locked]
    public ?int $editingHolidayId = null;

    /** @var array{name_en: string, name_ar: string, scope: string, kind: string, projects: list<string>, start_date: string, end_date: string, is_recurring: bool} */
    public array $holidayForm = [
        'name_en' => '',
        'name_ar' => '',
        'scope' => Holiday::SCOPE_PROJECTS,
        'kind' => Holiday::KIND_OFFICIAL,
        'projects' => [],
        'start_date' => '',
        'end_date' => '',
        'is_recurring' => false,
    ];

    protected function holidayUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    #[Computed]
    public function isNationalHr(): bool
    {
        return app(HolidayProjects::class)->isNationalHr($this->holidayUser());
    }

    /**
     * The project picker, one group per governorate (H-2): its header ticks
     * or clears every project there. Only per-project links are stored;
     * there is no governorate-wide holiday.
     *
     * @return list<array{id: int, name: string, options: list<array{value: string, label: string}>}>
     */
    #[Computed]
    public function projectGroups(): array
    {
        $groups = app(HolidayProjects::class)->managedBy($this->holidayUser())
            ->groupBy('city_id', preserveKeys: true)
            ->map(fn (Collection $projects, int $cityId): array => [
                'id' => $cityId,
                'name' => (string) $projects->first()['city'],
                'options' => array_values($projects
                    ->map(fn (array $project, string $key): array => ['value' => $key, 'label' => $project['program']])
                    ->sortBy('label')
                    ->all()),
            ])
            ->sortBy('name');

        return array_values($groups->all());
    }

    public function openCreateHoliday(?string $date = null): void
    {
        Gate::authorize('create', Holiday::class);

        $this->resetHolidayForm();

        if ($date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            $this->holidayForm['start_date'] = $date;
            $this->holidayForm['end_date'] = $date;
        }

        $this->showHolidayModal = true;
    }

    public function openEditHoliday(int $holidayId): void
    {
        $holiday = Holiday::query()->with('projects:id,holiday_id,program_id,city_id')->findOrFail($holidayId);

        Gate::authorize('update', $holiday);

        $this->resetHolidayForm();
        $this->editingHolidayId = $holiday->id;
        $this->holidayForm = [
            'name_en' => (string) $holiday->name_en,
            'name_ar' => (string) $holiday->name_ar,
            'scope' => $holiday->scope,
            'kind' => $holiday->kind,
            'projects' => $holiday->projects->map(fn (HolidayProject $link): string => $link->program_id.':'.$link->city_id)->values()->all(),
            'start_date' => (string) $holiday->start_date->toDateString(),
            'end_date' => (string) $holiday->end_date->toDateString(),
            'is_recurring' => $holiday->is_recurring,
        ];
        $this->showHolidayModal = true;
    }

    /** H-2 shortcut: tick every project of one governorate, keeping what is ticked elsewhere. */
    public function selectCityProjects(int $cityId): void
    {
        $this->holidayForm['projects'] = array_values(array_unique([...$this->holidayForm['projects'], ...$this->cityProjectKeys($cityId)]));
    }

    /** Untick every project of one governorate, keeping what is ticked elsewhere. */
    public function clearCityProjects(int $cityId): void
    {
        $this->holidayForm['projects'] = array_values(array_diff($this->holidayForm['projects'], $this->cityProjectKeys($cityId)));
    }

    public function saveHoliday(): void
    {
        try {
            $holiday = app(SaveHoliday::class)->handle($this->holidayUser(), $this->editingHolidayId, $this->holidayForm);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $field): array => ['holidayForm.'.$field => $messages])->all(),
            );
        }

        $created = $this->editingHolidayId === null;

        $this->closeHolidayModal();
        $this->holidaysChanged();

        Flux::toast(variant: 'success', text: __($created ? 'hr_leave.calendar.holidays.created' : 'hr_leave.calendar.holidays.updated', ['name' => $holiday->name]));
    }

    public function closeHolidayModal(): void
    {
        $this->showHolidayModal = false;
        $this->resetHolidayForm();
    }

    /** Called after a save or delete, so the page can forget its computed rows. */
    abstract protected function holidaysChanged(): void;

    /**
     * The picker keys ("programId:cityId") of the user's projects in one governorate.
     *
     * @return list<string>
     */
    private function cityProjectKeys(int $cityId): array
    {
        return array_values(array_map(
            strval(...),
            app(HolidayProjects::class)->managedBy($this->holidayUser())
                ->filter(fn (array $project): bool => $project['city_id'] === $cityId)
                ->keys()
                ->all(),
        ));
    }

    private function resetHolidayForm(): void
    {
        $this->resetValidation();
        $this->editingHolidayId = null;
        $this->holidayForm = [
            'name_en' => '',
            'name_ar' => '',
            'scope' => $this->isNationalHr ? Holiday::SCOPE_NATIONAL : Holiday::SCOPE_PROJECTS,
            'kind' => Holiday::KIND_OFFICIAL,
            'projects' => [],
            'start_date' => '',
            'end_date' => '',
            'is_recurring' => false,
        ];
    }
}
