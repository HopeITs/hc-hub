<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Attendance;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * Whose timesheets a user may see, by the active role, the way the other HR pages scope people:
 *  - an employee: their own;
 *  - a manager: their own and their direct reports' (people on positions that report to the
 *    manager's current positions, as Contract::scopeVisibleTo does);
 *  - an HR role of a city: everyone of that city; HR Syria, the general manager and the
 *    administrators: everyone.
 * `attendance.view` lifts a user above "own"; `attendance.view-own` is every role's.
 */
class Roster
{
    public const OWN = 'own';

    public const TEAM = 'team';

    public const CITY = 'city';

    public const ALL = 'all';

    /** How far this user sees: own, team, city or all. */
    public function mode(User $user): string
    {
        if (! $user->can('attendance.view')) {
            return self::OWN;
        }

        if (HrAccess::is($user, 'manager')) {
            return self::TEAM;
        }

        if (HrAccess::is($user, 'HR')) {
            return HrAccess::cityIds($user) === null ? self::ALL : self::CITY;
        }

        if ($user->isAdministrator() || HrAccess::is($user, 'general-manager', 'super-admin')) {
            return self::ALL;
        }

        // Granted attendance.view by hand to a role that is not a team, city or head-office role.
        return self::OWN;
    }

    /** @return Collection<int, Employee> */
    public function employees(User $user, CarbonImmutable $month, ?int $cityId = null): Collection
    {
        $mode = $this->mode($user);

        return $this->scope($user, Employee::query(), $month)
            ->with('city')
            ->whereHas('allContracts', fn (Builder $contract): Builder => $this->overlapping($contract, $month))
            ->when($cityId !== null && $mode !== self::OWN, fn (Builder $query): Builder => $query->where('city_id', $cityId))
            ->orderBy('first_name_en')
            ->orderBy('last_name_en')
            ->get();
    }

    /**
     * Restrict an employee query to the people this user may see for the month.
     *
     * @param  Builder<Employee>  $query
     * @return Builder<Employee>
     */
    public function scope(User $user, Builder $query, CarbonImmutable $month): Builder
    {
        $own = $user->employee_id !== null ? (int) $user->employee_id : null;

        switch ($this->mode($user)) {
            case self::ALL:
                return $query;

            case self::CITY:
                return $query->whereIn($query->qualifyColumn('city_id'), HrAccess::cityIds($user) ?? []);

            case self::TEAM:
                $positionIds = $own !== null
                    ? Contract::query()->activeNow()->where('employee_id', $own)->pluck('position_id')->map(fn (mixed $id): int => (int) $id)->all()
                    : [];

                return $query->where(function (Builder $q) use ($own, $positionIds, $month): void {
                    $own !== null ? $q->whereKey($own) : $q->whereRaw('1 = 0');

                    $q->orWhereHas('allContracts', fn (Builder $contract): Builder => $this->overlapping($contract, $month)
                        ->whereHas('position', fn (Builder $position): Builder => $position->whereIn('direct_manager_id', $positionIds)));
                });

            default:
                return $own !== null ? $query->whereKey($own) : $query->whereRaw('1 = 0');
        }
    }

    /** May this user see this employee's timesheet for the month? */
    public function covers(User $user, Employee $employee, ?CarbonImmutable $month = null): bool
    {
        return $this->scope($user, Employee::query(), $month ?? CarbonImmutable::now()->startOfMonth())
            ->whereKey($employee->id)
            ->exists();
    }

    /**
     * The cities to filter by: only for the roles that see more than one place at a time.
     *
     * @return Collection<int, City>
     */
    public function cities(User $user): Collection
    {
        if (! in_array($this->mode($user), [self::CITY, self::ALL], true)) {
            return collect();
        }

        $cityIds = HrAccess::cityIds($user);

        return City::query()
            ->active()
            ->when($cityIds !== null, fn (Builder $query): Builder => $query->whereIn('id', $cityIds ?? []))
            ->orderBy(City::nameColumn())
            ->get();
    }

    /**
     * The city the page starts on. Only for those who see every city (HR Syria, the general
     * manager, the administrators): their own city, else the first. Everyone else is already
     * limited by role (own, team or their cities), so nothing is chosen for them.
     */
    public function defaultCityId(User $user): ?int
    {
        if ($this->mode($user) !== self::ALL) {
            return null;
        }

        $cities = $this->cities($user);
        $own = $user->employee_id === null ? null : Employee::query()->whereKey($user->employee_id)->value('city_id');

        $cityId = $own !== null && $cities->contains('id', (int) $own) ? (int) $own : $cities->first()?->id;

        return $cityId === null ? null : (int) $cityId;
    }

    /**
     * Contracts (not drafts) running some day of the month.
     *
     * @template TQuery of Builder|Relation
     *
     * @param  TQuery  $contract
     * @return TQuery
     */
    public function overlapping(Builder|Relation $contract, CarbonImmutable $month): Builder|Relation
    {
        return $contract
            ->where(fn (Builder $type): Builder => $type->whereNull('contracts.type')->orWhere('contracts.type', '!=', ContractType::Draft->value))
            ->whereDate('contracts.start_date', '<=', $month->endOfMonth()->toDateString())
            ->whereDate('contracts.end_date', '>=', $month->startOfMonth()->toDateString());
    }
}
