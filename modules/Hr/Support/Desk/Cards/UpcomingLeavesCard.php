<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Desk\TeamLeaves;

/**
 * Upcoming leaves: the accepted leaves of the viewer's team over the coming
 * two months, soonest first.
 */
final class UpcomingLeavesCard extends HrCard
{
    public const int HorizonDays = 61;

    public function key(): string
    {
        return 'upcoming-leaves';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null;
    }

    public function register(): ?string
    {
        return 'hr.team.index';
    }

    public function icon(): string
    {
        return 'cal';
    }

    public function layout(): string
    {
        return 'agenda';
    }

    public function figures(User $user): array
    {
        $leaves = $this->leaves($user);
        $week = CarbonImmutable::today()->addDays(7)->endOfDay();

        return [
            $this->figure(__('hr_desk.cards.upcoming-leaves.this_week'), $leaves->filter(fn (LeaveRequest $leave): bool => $leave->start_at->lte($week))->count(), 'warn'),
            $this->figure(__('hr_desk.cards.upcoming-leaves.two_months'), $leaves->count(), 'active'),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->leaves($user)->map(function (LeaveRequest $leave): array {
            $employee = TeamLeaves::employeeOf($leave);

            return $this->shaped(
                $this->row('ul-'.$leave->id, (string) ($employee?->name ?? '—'), (string) ($leave->leaveBalance?->leaveType?->name ?? ''), ['label' => $this->span($leave->start_at, $leave->end_at), 'tone' => $leave->start_at->isPast() ? 'warn' : 'active'], $this->link('hr.team.index'), (string) $employee?->secondary_name),
                lead: $this->day($leave->start_at->isPast() ? today() : $leave->start_at, 'active'),
            );
        });
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    private function leaves(User $user): Collection
    {
        $today = CarbonImmutable::today();

        return once(fn (): Collection => app(TeamLeaves::class)->between($this->viewer($user)->teamIds(), $today, $today->addDays(self::HorizonDays)));
    }
}
