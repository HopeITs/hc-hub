<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Employees;

use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Actions\Contracts\ReservePosition;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Employees\DigitalId;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * The small edits made from the profile page itself: the contact and skills
 * sections, the ID card, and holding a position for a new employee.
 */
trait ManagesEmployeeProfile
{
    /** The profile sections edited in place, and the columns each holds. */
    public const SECTIONS = [
        'personal-contact' => ['personal_phone', 'personal_email', 'address_ar', 'address_en'],
        'work-contact' => ['work_phone', 'whatsapp_work', 'telegram_work'],
        'skills' => ['skills', 'interests'],
    ];

    public bool $showSectionModal = false;

    public string $editingSection = '';

    /** @var array<string, string|null> */
    public array $sectionFields = [];

    /** A hash of the section as it was opened: a save over someone else's change is refused. */
    public string $sectionVersion = '';

    public bool $showDraftModal = false;

    public string $draftCityId = '';

    public string $draftProgramId = '';

    public string $draftPositionId = '';

    /**
     * HR edits every section; the employee edits their own personal contact
     * details and skills while they are active.
     */
    public function canEditSection(string $section): bool
    {
        if (! array_key_exists($section, self::SECTIONS)) {
            return false;
        }

        $self = $this->isSelf && $this->employee->is_active;

        return $section === 'work-contact' ? $this->canEdit : ($this->canEdit || $self);
    }

    public function openSection(string $section): void
    {
        abort_unless($this->canEditSection($section), 404);

        $this->resetValidation();
        $this->editingSection = $section;
        $this->sectionFields = $this->sectionValues($this->employee, $section);
        $this->sectionVersion = $this->versionOf($this->sectionFields);
        $this->showSectionModal = true;
    }

    public function closeSection(): void
    {
        $this->showSectionModal = false;
        $this->editingSection = '';
        $this->sectionFields = [];
        $this->sectionVersion = '';
        $this->resetValidation();
    }

    public function saveSection(): void
    {
        $section = $this->editingSection;
        abort_unless($this->canEditSection($section), 404);

        foreach (['personal_phone', 'work_phone'] as $phone) {
            if (filled($this->sectionFields[$phone] ?? null)) {
                $this->sectionFields[$phone] = $this->westernDigits((string) $this->sectionFields[$phone]);
            }
        }

        $rules = array_intersect_key([
            'personal_phone' => ['nullable', 'regex:/^09\d{8}$/'],
            'work_phone' => ['nullable', 'regex:/^09\d{8}$/'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'address_ar' => ['nullable', 'string', 'max:255'],
            'address_en' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'string', 'max:255'],
            'interests' => ['nullable', 'string', 'max:255'],
            'whatsapp_work' => ['nullable', 'string', 'max:255'],
            'telegram_work' => ['nullable', 'string', 'max:255'],
        ], array_flip(self::SECTIONS[$section]));

        $prefixed = [];
        $attributes = [];

        foreach ($rules as $field => $fieldRules) {
            $prefixed['sectionFields.'.$field] = $fieldRules;
            $attributes['sectionFields.'.$field] = __('hr_employees.fields.'.$field);
        }

        $validated = $this->validate($prefixed, [
            'sectionFields.personal_phone.regex' => __('hr_employees.validation.phone_format'),
            'sectionFields.work_phone.regex' => __('hr_employees.validation.phone_format'),
        ], $attributes)['sectionFields'] ?? [];

        $entries = array_map(fn (mixed $value): ?string => blank($value) ? null : trim((string) $value), $validated);

        $stale = false;

        DB::transaction(function () use ($section, $entries, &$stale): void {
            $current = Employee::query()->whereKey($this->employeeId)->lockForUpdate()->firstOrFail();

            if ($this->versionOf($this->sectionValues($current, $section)) !== $this->sectionVersion) {
                $stale = true;

                return;
            }

            $current->forceFill($entries)->save();
        });

        if ($stale) {
            $this->addError('section', __('hr_employees.profile.section_stale'));

            return;
        }

        unset($this->employee);
        $this->closeSection();

        Flux::toast(variant: 'success', text: __('hr_employees.toasts.section_saved'));
    }

    public function reissueCard(int $expectedIndex): void
    {
        abort_unless($this->canManageCard, 403);

        try {
            $index = DigitalId::reissue($this->employee, $expectedIndex);
        } catch (ValidationException $exception) {
            Flux::toast(variant: 'danger', text: $exception->validator->errors()->first());

            return;
        }

        RateLimiter::clear($this->cardReportKey());
        unset($this->employee);

        Flux::toast(variant: 'success', text: __('hr_employees.card.done.reissued', ['index' => $index]));
    }

    public function setCardEnabled(bool $enabled): void
    {
        abort_unless($this->canManageCard, 403);

        try {
            $enabled ? DigitalId::enable($this->employee) : DigitalId::disable($this->employee);
        } catch (ValidationException $exception) {
            Flux::toast(variant: 'danger', text: $exception->validator->errors()->first());

            return;
        }

        unset($this->employee);

        Flux::toast(variant: 'success', text: __($enabled ? 'hr_employees.card.done.enabled' : 'hr_employees.card.done.disabled'));
    }

    /**
     * The employee tells HR their card is lost: once an hour at most.
     */
    public function reportCardLost(): void
    {
        Gate::authorize('reportCardLost', $this->employee);

        $key = $this->cardReportKey();

        if (RateLimiter::tooManyAttempts($key, 1)) {
            Flux::toast(text: __('hr_employees.card.done.already_reported'));

            return;
        }

        RateLimiter::hit($key, 3600);
        event('hr.employee.card-reported-lost', [$this->employee, auth()->user()]);

        Flux::toast(variant: 'success', text: __('hr_employees.card.done.reported'));
    }

    public function openDraft(): void
    {
        abort_unless($this->canEdit, 404);

        $this->resetValidation();
        $this->draftCityId = (string) $this->employee->city_id;
        $this->draftProgramId = '';
        $this->draftPositionId = '';
        $this->showDraftModal = true;
    }

    public function closeDraft(): void
    {
        $this->showDraftModal = false;
        $this->resetValidation();
    }

    public function updatedDraftCityId(): void
    {
        $this->draftProgramId = '';
        $this->draftPositionId = '';
    }

    public function updatedDraftProgramId(): void
    {
        $this->draftPositionId = '';
    }

    public function saveDraft(): void
    {
        abort_unless($this->canEdit, 404);

        $this->validate(['draftPositionId' => ['required', 'integer']], [], ['draftPositionId' => __('hr_employees.fields.position')]);

        app(ReservePosition::class)->handle(
            $this->employee,
            (int) $this->draftPositionId,
            $this->draftCityId === '' ? null : (int) $this->draftCityId,
            $this->draftProgramId === '' ? null : (int) $this->draftProgramId,
        );

        $this->closeDraft();
        unset($this->contracts, $this->currentContract);

        Flux::toast(variant: 'success', text: __('hr_employees.toasts.position_reserved'));
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

    /**
     * @return array<string, string|null>
     */
    private function sectionValues(Employee $employee, string $section): array
    {
        $values = [];

        foreach (self::SECTIONS[$section] as $column) {
            $value = $employee->getAttribute($column);
            $values[$column] = $value === null ? null : (string) $value;
        }

        return $values;
    }

    /**
     * @param  array<string, string|null>  $values
     */
    private function versionOf(array $values): string
    {
        return hash('sha256', (string) json_encode($values));
    }

    private function westernDigits(string $value): string
    {
        return trim(strtr($value, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));
    }

    private function cardReportKey(): string
    {
        return 'hr-card-report-lost:'.$this->employeeId;
    }
}
