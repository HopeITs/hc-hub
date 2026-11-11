<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Leave;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Core\Models\City;
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

    /** The governorate whose projects "Select all its projects" ticks. */
    public string $holidayGovernorate = '';

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

    /** @return array<string, string> "programId:cityId" => label */
    #[Computed]
    public function projectOptions(): array
    {
        return app(HolidayProjects::class)->managedBy($this->holidayUser())->map(fn (array $project): string => $project['label'])->all();
    }

    /** @return array<int, string> the governorates of the user's projects */
    #[Computed]
    public function projectGovernorates(): array
    {
        $cityIds = app(HolidayProjects::class)->managedBy($this->holidayUser())->pluck('city_id')->unique()->all();

        return City::query()
            ->whereIn('id', $cityIds === [] ? [0] : $cityIds)
            ->orderBy(City::nameColumn())
            ->get(['id', 'name_en', 'name_ar'])
            ->mapWithKeys(fn (City $city): array => [(int) $city->id => (string) $city->name])
            ->all();
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

    /** H-2 shortcut: tick every project of one governorate (per-project links, never a governorate-wide holiday). */
    public function selectGovernorateProjects(): void
    {
        $keys = app(HolidayProjects::class)->managedBy($this->holidayUser())
            ->filter(fn (array $project): bool => $project['city_id'] === (int) $this->holidayGovernorate)
            ->keys()
            ->all();

        $this->holidayForm['projects'] = array_values(array_unique([...$this->holidayForm['projects'], ...$keys]));
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

        Flux::toast(variant: 'success', text: __($created ? 'hr_leave_calendar.holidays.created' : 'hr_leave_calendar.holidays.updated', ['name' => $holiday->name]));
    }

    public function closeHolidayModal(): void
    {
        $this->showHolidayModal = false;
        $this->resetHolidayForm();
    }

    /** Called after a save or delete, so the page can forget its computed rows. */
    abstract protected function holidaysChanged(): void;

    private function resetHolidayForm(): void
    {
        $this->resetValidation();
        $this->editingHolidayId = null;
        $this->holidayGovernorate = '';
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
