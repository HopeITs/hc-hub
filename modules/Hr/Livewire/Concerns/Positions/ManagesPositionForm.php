<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Positions;

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Hr\Actions\Positions\SavePosition;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * The position create / edit modal on the positions register.
 */
trait ManagesPositionForm
{
    private const FORM_FIELDS = ['title_en', 'title_ar', 'program_id', 'city_id', 'center_id', 'direct_manager_id', 'tech_manager_id',
        'job_level_id', 'department_id', 'qualifications', 'responsibilities', 'max_emp', 'is_active'];

    public bool $showPositionModal = false;

    #[Locked]
    public ?int $editingPositionId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    public function openPosition(?int $positionId = null): void
    {
        $this->resetValidation();
        $this->editingPositionId = null;
        $this->form = array_fill_keys(self::FORM_FIELDS, '');
        $this->form['is_active'] = true;
        $this->form['max_emp'] = '1';

        if ($positionId !== null) {
            $position = Position::query()->findOrFail($positionId);
            Gate::authorize('update', $position);

            $this->editingPositionId = $position->id;

            foreach (self::FORM_FIELDS as $field) {
                $value = $position->getAttribute($field);
                $this->form[$field] = is_bool($value) ? $value : (string) ($value ?? '');
            }
        } else {
            Gate::authorize('create', Position::class);

            $cities = HrAccess::cityIds(Auth::user());

            if ($cities !== null && count($cities) === 1) {
                $this->form['city_id'] = (string) $cities[0];
            }
        }

        $this->showPositionModal = true;
    }

    public function closePosition(): void
    {
        $this->showPositionModal = false;
        $this->editingPositionId = null;
    }

    public function savePosition(): void
    {
        $position = $this->editingPositionId === null ? null : Position::query()->findOrFail($this->editingPositionId);

        app(SavePosition::class)->handle($position, $this->form, Auth::user());

        $this->closePosition();
        unset($this->positions);
        Flux::toast(variant: 'success', text: __('hr_positions.toasts.saved'));
    }

    /**
     * Whether the position being edited is critical; null when the viewer may
     * not set the tag (HR and the administrators only) or none is open.
     */
    #[Computed]
    public function criticalMark(): ?bool
    {
        if ($this->editingPositionId === null) {
            return null;
        }

        $position = Position::query()->select(['id', 'city_id', 'center_id', 'is_critical'])->find($this->editingPositionId);

        return $position !== null && Gate::allows('markCritical', $position) ? $position->is_critical : null;
    }

    /** Set or clear the critical tag (PositionPolicy::markCritical). */
    public function toggleCritical(): void
    {
        $position = Position::query()->findOrFail((int) $this->editingPositionId);

        Gate::authorize('markCritical', $position);

        $position->update(['is_critical' => ! $position->is_critical]);

        unset($this->criticalMark, $this->positions);
        Flux::toast(variant: 'success', text: __($position->is_critical ? 'hr_positions.critical.marked' : 'hr_positions.critical.unmarked'));
    }

    public function updatedFormCityId(): void
    {
        $this->form['center_id'] = '';
    }

    /**
     * @return array<int, string>
     */
    public function jobLevelOptions(): array
    {
        return Cache::remember('hr.job-levels.'.app()->getLocale(), now()->addHour(), fn (): array => JobLevel::query()
            ->select(['id', 'name_en', 'name_ar', 'grade'])
            ->orderBy('grade')
            ->get()
            ->mapWithKeys(fn (JobLevel $level): array => [$level->id => (string) $level->name])
            ->all());
    }

    /**
     * @return array<int, string>
     */
    public function centerOptions(): array
    {
        $cityId = filled($this->form['city_id'] ?? null) ? (int) $this->form['city_id'] : null;

        return Center::options(City::isNational($cityId) ? null : $cityId);
    }
}
