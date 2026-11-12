<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Leave;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Holiday;
use Modules\Hr\Models\HolidayProject;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Settings\HrAccess;

/**
 * Who may set which holidays (team-coverage-contract §6.1, H-1 … H-6).
 *
 * A project is a program in one governorate, headed by a project-manager
 * position (TeamCoverage::PROJECT_MANAGER_GRADE). National HR (HR-SYR,
 * administrators) sets any holiday. A program manager sets project holidays
 * and one-off grants only for the projects whose project-manager position
 * reports to one of their own positions: responsibility follows
 * direct_manager_id, not program_id. The picker and the validation both read
 * this class, so they cannot drift.
 */
final class HolidayProjects
{
    /** @var array<string, Collection<string, array{program_id: int, city_id: int, program: string, city: string, label: string}>> */
    private array $managed = [];

    /** @var Collection<string, array{program_id: int, city_id: int, program: string, city: string, label: string}>|null */
    private ?Collection $all = null;

    public function isNationalHr(?User $user): bool
    {
        return $user !== null && HrAccess::for($user)->isNational();
    }

    public function canManage(?User $user): bool
    {
        return $this->isNationalHr($user) || $this->managesAnyProject($user);
    }

    /** One cheap query, for the menu: does a project-manager position report to one of $user's positions? */
    public function managesAnyProject(?User $user): bool
    {
        if ($user?->employee_id === null) {
            return false;
        }

        $own = Contract::query()->activeNow()->where('employee_id', $user->employee_id)->select('position_id');

        return $this->projectManagerPositions()->whereIn('direct_manager_id', $own)->exists();
    }

    /**
     * Every project, keyed "programId:cityId".
     *
     * @return Collection<string, array{program_id: int, city_id: int, program: string, city: string, label: string}>
     */
    public function all(): Collection
    {
        return $this->all ??= $this->toProjects($this->projectManagerPositions()->get());
    }

    /**
     * The projects $user may set holidays for: all of them for national HR.
     *
     * @return Collection<string, array{program_id: int, city_id: int, program: string, city: string, label: string}>
     */
    public function managedBy(?User $user): Collection
    {
        if ($user === null) {
            return collect();
        }

        if ($this->isNationalHr($user)) {
            return $this->all();
        }

        $key = $user->getKey().':'.HrAccess::for($user)->role();

        if (isset($this->managed[$key])) {
            return $this->managed[$key];
        }

        $ownPositionIds = $user->employee_id !== null
            ? Contract::query()->activeNow()->where('employee_id', $user->employee_id)->pluck('position_id')->filter()->unique()->all()
            : [];

        return $this->managed[$key] = $ownPositionIds === []
            ? collect()
            : $this->toProjects($this->projectManagerPositions()->whereIn('direct_manager_id', $ownPositionIds)->get());
    }

    /** May $user change this existing holiday? Every one of its projects must be theirs. */
    public function canChange(?User $user, Holiday $holiday): bool
    {
        if ($this->isNationalHr($user)) {
            return true;
        }

        if ($holiday->isNational() || $holiday->kind === Holiday::KIND_BIRTHDAY) {
            return false;
        }

        $links = $holiday->relationLoaded('projects') ? $holiday->projects : $holiday->projects()->get(['program_id', 'city_id']);
        $mine = $this->managedBy($user);

        return $links->isNotEmpty()
            && $links->every(fn (HolidayProject $link): bool => $mine->has($link->program_id.':'.$link->city_id));
    }

    /**
     * The non-birthday holidays $user looks after: every one for national
     * HR, those on any of their projects for a program manager.
     *
     * @return Builder<Holiday>
     */
    public function holidaysOf(?User $user): Builder
    {
        $query = Holiday::query()->with('projects')->where('kind', '!=', Holiday::KIND_BIRTHDAY);

        if ($this->isNationalHr($user)) {
            return $query;
        }

        return $this->onProjects($query, $this->managedBy($user));
    }

    /**
     * The holidays list of the holidays page: everything for national HR,
     * the national ones (read only) and those on their projects for a
     * program manager.
     *
     * @return Builder<Holiday>
     */
    public function visibleTo(?User $user): Builder
    {
        $query = Holiday::query();

        if ($this->isNationalHr($user)) {
            return $query;
        }

        $mine = $this->managedBy($user);

        return $query->where(fn (Builder $q) => $q
            ->where('scope', Holiday::SCOPE_NATIONAL)
            ->orWhere(fn (Builder $own) => $this->onProjects($own, $mine)));
    }

    /**
     * @param  Builder<Holiday>  $query
     * @param  Collection<string, array{program_id: int, city_id: int, program: string, city: string, label: string}>  $projects
     * @return Builder<Holiday>
     */
    private function onProjects(Builder $query, Collection $projects): Builder
    {
        if ($projects->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('projects', fn (Builder $link) => $link->where(function (Builder $any) use ($projects): void {
            foreach ($projects as $project) {
                $any->orWhere(fn (Builder $pair) => $pair->where('program_id', $project['program_id'])->where('city_id', $project['city_id']));
            }
        }));
    }

    /** @return Builder<Position> */
    private function projectManagerPositions(): Builder
    {
        return Position::query()
            ->where('positions.is_active', true)
            ->whereNotNull('program_id')
            ->whereHas('jobLevel', fn (Builder $level) => $level->where('grade', TeamCoverage::PROJECT_MANAGER_GRADE))
            ->with(['program:id,name', 'center:id,city_id', 'center.city:id,name_en,name_ar', 'city:id,name_en,name_ar']);
    }

    /**
     * @param  Collection<int, Position>  $positions
     * @return Collection<string, array{program_id: int, city_id: int, program: string, city: string, label: string}>
     */
    private function toProjects(Collection $positions): Collection
    {
        return $positions
            ->map(function (Position $position): ?array {
                $city = $position->center?->city ?? $position->city;

                return $city === null ? null : [
                    'program_id' => (int) $position->program_id,
                    'city_id' => (int) $city->id,
                    'program' => (string) $position->program?->name,
                    'city' => (string) $city->name,
                    'label' => $position->program?->name.' — '.$city->name,
                ];
            })
            ->filter()
            ->keyBy(fn (array $project): string => $project['program_id'].':'.$project['city_id'])
            ->sortBy('label');
    }
}
