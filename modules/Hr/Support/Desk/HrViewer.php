<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk;

use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;
use WeakMap;

/**
 * Who is looking at an HR card: their employee record, their current
 * contract and position, and how far their HR role reaches.
 *
 * Reach follows the old HR app: an administrator, HR-SYR or the general
 * manager sees the whole organisation; a regional HR role (HR-ALP) sees its
 * own governorates; a manager sees the people who report to their position;
 * everyone else sees the colleagues who share their manager. The hub adds
 * roles together, so the widest reach among a person's roles wins.
 */
final class HrViewer
{
    /**
     * Roles that see every governorate.
     *
     * @var list<string>
     */
    public const array OrgRoles = ['HR-SYR', 'general-manager', 'General Manager', 'super-admin'];

    /**
     * How long a person's team (a list of employee ids) is remembered.
     */
    private const int TeamCacheSeconds = 300;

    /**
     * @var WeakMap<User, self>|null
     */
    private static ?WeakMap $viewers = null;

    private bool $contractRead = false;

    private ?Contract $contract = null;

    /**
     * @var list<int>|null
     */
    private ?array $team = null;

    /**
     * @var list<int>|null
     */
    private ?array $positions = null;

    /**
     * @var list<int>|null
     */
    private ?array $reports = null;

    private function __construct(public readonly User $user) {}

    public static function for(User $user): self
    {
        self::$viewers ??= new WeakMap;

        return self::$viewers[$user] ??= new self($user);
    }

    public function employeeId(): ?int
    {
        return $this->user->employee_id === null ? null : (int) $this->user->employee_id;
    }

    /**
     * The viewer's running contract (newest first) with its position and centre.
     */
    public function contract(): ?Contract
    {
        if ($this->contractRead) {
            return $this->contract;
        }

        $this->contractRead = true;

        if ($this->employeeId() === null) {
            return null;
        }

        return $this->contract = Contract::query()
            ->activeNow()
            ->where('employee_id', $this->employeeId())
            ->with(['position:id,program_id,center_id,city_id,department_id,direct_manager_id,title_en,title_ar', 'position.center:id,city_id,open_at,close_at,weekends'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * The governorate the viewer works in: their centre's, else their position's, else their own.
     */
    public function cityId(): ?int
    {
        $position = $this->contract()?->position;
        $cityId = $position?->center?->city_id ?? $position?->city_id;

        if ($cityId !== null) {
            return (int) $cityId;
        }

        $own = $this->employeeId() === null ? null : Employee::query()->whereKey($this->employeeId())->value('city_id');

        return $own === null ? null : (int) $own;
    }

    public function programId(): ?int
    {
        $programId = $this->contract()?->position?->program_id;

        return $programId === null ? null : (int) $programId;
    }

    /**
     * The code of the department the viewer's running contract sits in ('IT', ...).
     */
    public function departmentCode(): ?string
    {
        $departmentId = $this->contract()?->position?->department_id;

        return $departmentId === null ? null : Department::codeOf((int) $departmentId);
    }

    public function seesOrganisation(): bool
    {
        return $this->user->isAdministrator() || $this->user->hasAnyRole(self::OrgRoles);
    }

    /**
     * Whether the viewer holds any HR role (regional or national).
     */
    public function isHr(): bool
    {
        return $this->user->getRoleNames()->contains(fn (string $role): bool => str_starts_with(strtoupper($role), 'HR-'));
    }

    public function isManager(): bool
    {
        return $this->user->hasAnyRole(['manager', 'Managers']);
    }

    /**
     * The governorates a regional HR role is held to; null when the viewer
     * is not held to any (organisation-wide, or no HR role at all).
     *
     * @return list<int>|null
     */
    public function hrCityIds(): ?array
    {
        if ($this->seesOrganisation() || ! $this->isHr()) {
            return null;
        }

        $bySlug = City::query()->pluck('id', 'slug')->mapWithKeys(fn (mixed $id, mixed $slug): array => [strtoupper(trim((string) $slug)) => (int) $id]);
        $ids = [];

        foreach ($this->user->getRoleNames() as $role) {
            if (! str_starts_with(strtoupper((string) $role), 'HR-')) {
                continue;
            }

            foreach (City::slugsForHrRole((string) $role) as $slug) {
                if (isset($bySlug[strtoupper($slug)])) {
                    $ids[] = $bySlug[strtoupper($slug)];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * The governorates the viewer's HR lists are narrowed to: null for no
     * narrowing (organisation-wide), a list for a regional HR role, and an
     * empty list for someone with no HR reach at all.
     *
     * @return list<int>|null
     */
    public function cityScope(): ?array
    {
        if ($this->seesOrganisation()) {
            return null;
        }

        return $this->hrCityIds() ?? [];
    }

    /**
     * The active employees the viewer's cards are about, themselves included
     * (remembered for five minutes per person).
     *
     * @return list<int>
     */
    public function teamIds(): array
    {
        return $this->team ??= array_map('intval', Cache::remember(
            'hr.desk.team.'.$this->user->id.'.'.md5($this->user->getRoleNames()->sort()->implode('|')),
            self::TeamCacheSeconds,
            fn (): array => $this->readTeamIds(),
        ));
    }

    /**
     * The viewer's team without themselves.
     *
     * @return list<int>
     */
    public function othersIds(): array
    {
        return array_values(array_diff($this->teamIds(), [(int) $this->employeeId()]));
    }

    /**
     * The positions the viewer holds now.
     *
     * @return list<int>
     */
    public function positionIds(): array
    {
        if ($this->employeeId() === null) {
            return [];
        }

        return $this->positions ??= Contract::query()->activeNow()->where('employee_id', $this->employeeId())
            ->pluck('position_id')->filter()->map(fn (mixed $id): int => (int) $id)->unique()->values()->all();
    }

    /**
     * The employees whose position reports to one of the viewer's positions.
     *
     * @return list<int>
     */
    public function reportIds(): array
    {
        $positions = $this->positionIds();

        return $this->reports ??= $positions === [] ? [] : $this->employeesUnder($positions);
    }

    /**
     * @return list<int>
     */
    private function readTeamIds(): array
    {
        $active = Employee::query()->where('is_active', true);

        if ($this->seesOrganisation()) {
            return $active->pluck('id')->all();
        }

        $cities = $this->hrCityIds();

        if ($cities !== null) {
            return $active->whereIn('city_id', $cities)->pluck('id')->all();
        }

        $self = $this->employeeId();

        if ($self === null) {
            return [];
        }

        $reports = $this->reportIds();

        if ($reports !== []) {
            return array_values(array_unique([$self, ...$reports]));
        }

        $managers = Contract::query()->activeNow()->where('contracts.employee_id', $self)
            ->join('positions', 'positions.id', '=', 'contracts.position_id')
            ->whereNotNull('positions.direct_manager_id')
            ->pluck('positions.direct_manager_id')
            ->map(fn (mixed $id): int => (int) $id)->unique()->values()->all();

        return array_values(array_unique([$self, ...($managers === [] ? [] : $this->employeesUnder($managers))]));
    }

    /**
     * @param  list<int>  $managerPositionIds
     * @return list<int>
     */
    private function employeesUnder(array $managerPositionIds): array
    {
        return Contract::query()->activeNow()
            ->join('positions', 'positions.id', '=', 'contracts.position_id')
            ->join('employees', 'employees.id', '=', 'contracts.employee_id')
            ->whereIn('positions.direct_manager_id', $managerPositionIds)
            ->where('employees.is_active', true)
            ->distinct()
            ->pluck('contracts.employee_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
