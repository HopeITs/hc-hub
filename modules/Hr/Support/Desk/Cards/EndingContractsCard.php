<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\Employee;

/**
 * Contracts ending soon: running contracts that end within thirty days, the
 * nearest end first. HR sees its own governorates, a manager the contracts
 * of the positions that report to theirs.
 */
final class EndingContractsCard extends HrCard
{
    public const int EndingSoonDays = 30;

    public function key(): string
    {
        return 'ending-contracts';
    }

    public function allows(User $user): bool
    {
        return $user->can('contracts.view');
    }

    public function register(): ?string
    {
        return 'hr.contracts.index';
    }

    public function icon(): string
    {
        return 'file';
    }

    public function size(): string
    {
        return 'L';
    }

    public function layout(): string
    {
        return 'agenda';
    }

    public function figures(User $user): array
    {
        $contracts = $this->contracts($user);
        $week = today()->addDays(7);

        return [
            $this->figure(__('hr_desk.cards.ending-contracts.this_week'), $contracts->filter(fn (Contract $contract): bool => $contract->end_date->lte($week))->count(), 'critical'),
            $this->figure(__('hr_desk.cards.ending-contracts.this_month'), $contracts->count(), 'warn'),
        ];
    }

    public function health(User $user): ?string
    {
        return $this->contracts($user)->isNotEmpty() ? 'warn' : null;
    }

    protected function records(User $user, string $search): Collection
    {
        $title = app()->getLocale() === 'ar' ? 'title_ar' : 'title_en';

        return $this->contracts($user)->map(function (Contract $contract) use ($title): array {
            $days = (int) today()->diffInDays($contract->end_date, false);
            $tone = $days <= 7 ? 'critical' : 'warn';
            $position = $contract->position;

            return $this->shaped(
                $this->row(
                    'ec-'.$contract->id,
                    (string) ($contract->employee?->name ?? '—'),
                    trim(implode(' · ', array_filter([(string) ($position?->{$title} ?: $position?->title_en), $position?->department_id === null ? '' : Department::nameOf((int) $position->department_id), City::nameOf($contract->employee?->city_id === null ? null : (int) $contract->employee->city_id)]))),
                    ['label' => $this->away($contract->end_date), 'tone' => $tone],
                    $this->link('hr.contracts.show', $contract->id) ?: $this->link('hr.contracts.index'),
                    (string) $contract->employee?->secondary_name,
                ),
                lead: $this->day($contract->end_date, $tone),
            );
        });
    }

    /**
     * The running contracts ending within EndingSoonDays that the viewer
     * oversees, soonest first. Also read by the HR calendar (CalendarCard).
     *
     * @return Collection<int, Contract>
     */
    public function contracts(User $user): Collection
    {
        $viewer = $this->viewer($user);

        return once(function () use ($viewer): Collection {
            $query = Contract::query()
                ->select(['contracts.id', 'contracts.employee_id', 'contracts.position_id', 'contracts.end_date'])
                ->inView('ending-soon', self::EndingSoonDays);

            if (! $viewer->seesOrganisation()) {
                $cities = $viewer->hrCityIds();

                if ($cities !== null) {
                    $query->whereHas('employee', fn (Builder $employee) => $employee->whereIn('city_id', $cities));
                } elseif ($viewer->positionIds() !== []) {
                    $query->whereHas('position', fn (Builder $position) => $position->whereIn('direct_manager_id', $viewer->positionIds()));
                } else {
                    return new Collection;
                }
            }

            return $query
                ->with(['employee:'.implode(',', [...Employee::NameColumns, 'city_id']), 'position:id,title_en,title_ar,department_id'])
                ->orderBy('contracts.end_date')
                ->limit(self::RowLimit)
                ->get();
        });
    }
}
