<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\LeaveBalance;

/**
 * My leave balance: what is left of each leave type on the viewer's current
 * contract, the annual leave first. Balances are kept in hours; a day is the
 * contract's own working day.
 */
final class LeaveBalanceCard extends HrCard
{
    public function key(): string
    {
        return 'leave-balance';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null && $user->can('leaves.view-own');
    }

    public function register(): ?string
    {
        return 'hr.leave.requests.index';
    }

    public function icon(): string
    {
        return 'cal';
    }

    public function layout(): string
    {
        return 'meter';
    }

    public function figures(User $user): array
    {
        $annual = $this->balances($user)->first(fn (LeaveBalance $balance): bool => (int) $balance->type_id === LeaveTypeCases::Annual);

        if ($annual === null) {
            return [];
        }

        $hours = $this->hoursPerDay($user);

        return [
            $this->figure(__('hr_desk.cards.leave-balance.days_left'), (int) floor(max(0.0, $annual->remainingHours()) / $hours), 'active'),
            $this->figure(__('hr_desk.cards.leave-balance.days_total'), (int) floor($annual->deserved / $hours)),
            $this->figure(__('hr_desk.cards.leave-balance.days_used'), (int) floor($annual->used / $hours), 'warn'),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        $hours = $this->hoursPerDay($user);

        return $this->balances($user)
            ->filter(fn (LeaveBalance $balance): bool => $balance->deserved > 0)
            ->sortBy(fn (LeaveBalance $balance): int => (int) $balance->type_id === LeaveTypeCases::Annual ? 0 : 1)
            ->values()
            ->map(function (LeaveBalance $balance) use ($hours): array {
                $left = max(0.0, $balance->remainingHours());
                $share = $balance->deserved > 0 ? $left / $balance->deserved * 100 : 0.0;

                return $this->shaped(
                    $this->row('lb-'.$balance->id, (string) ($balance->leaveType?->name ?? '—'), (string) __('hr_desk.cards.leave-balance.of_days', ['total' => $this->number(floor($balance->deserved / $hours))]), null, $this->link('hr.leave.requests.index')),
                    end: ['big' => $this->number(floor($left / $hours)), 'small' => (string) __('hr_desk.days_short')],
                    bar: ['to' => $share, 'tone' => $share < 20 ? 'warn' : 'good'],
                );
            });
    }

    /**
     * @return Collection<int, LeaveBalance>
     */
    private function balances(User $user): Collection
    {
        $contract = $this->viewer($user)->contract();

        if ($contract === null) {
            return new Collection;
        }

        return once(fn (): Collection => LeaveBalance::query()
            ->select(['id', 'contract_id', 'type_id', 'deserved', 'used'])
            ->where('contract_id', $contract->id)
            ->with('leaveType:id,name_en,name_ar')
            ->get());
    }

    private function hoursPerDay(User $user): float
    {
        $hours = (float) ($this->viewer($user)->contract()?->work_hour_per_day ?? 0);

        return $hours > 0 ? $hours : 8.0;
    }
}
