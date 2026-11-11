<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\LeaveRequest;

/**
 * Needs your attention: what is about to run out or has been left waiting
 * on the viewer's own record, never anyone else's (ported from hc-hr). The
 * running contract ending within EndingDays with no later contract on
 * record, and the viewer's own leave requests still waiting although their
 * first day has passed.
 */
final class AttentionCard extends HrCard
{
    public const int EndingDays = 30;

    /**
     * The most waiting leave requests the card lists.
     */
    public const int WaitingLeaves = 3;

    public function key(): string
    {
        return 'attention';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null;
    }

    public function icon(): string
    {
        return 'flag';
    }

    public function size(): string
    {
        return 'S';
    }

    public function height(): int
    {
        return 3;
    }

    public function layout(): string
    {
        return 'agenda';
    }

    public function figures(User $user): array
    {
        return [];
    }

    public function health(User $user): ?string
    {
        return $this->notes($user)->isNotEmpty() ? 'warn' : null;
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->notes($user)->map(fn (array $note): array => $this->shaped(
            $this->row($note['key'], $note['title'], $note['meta'], ['label' => $note['badge'], 'tone' => 'warn'], $note['href']),
            lead: $this->day($note['date'], 'warn'),
        ));
    }

    /**
     * @return Collection<int, array{key: string, title: string, meta: string, badge: string, href: string, date: CarbonImmutable}>
     */
    private function notes(User $user): Collection
    {
        return once(function () use ($user): Collection {
            $viewer = $this->viewer($user);
            $employeeId = $viewer->employeeId();

            if ($employeeId === null) {
                return new Collection;
            }

            $today = CarbonImmutable::today();
            $notes = new Collection;
            $contract = $viewer->contract();
            $end = $contract?->end_date === null ? null : CarbonImmutable::parse($contract->end_date)->startOfDay();

            if ($end !== null && $end->lte($today->addDays(self::EndingDays))
                && ! Contract::query()->where('employee_id', $employeeId)->whereDate('start_date', '>', $end)->exists()) {
                $notes->push([
                    'key' => 'at-contract-'.$contract->id,
                    'title' => (string) __('hr_desk.cards.attention.contract'),
                    'meta' => $end->translatedFormat('j M Y'),
                    'badge' => $this->away($end),
                    'href' => $this->link('hr.employees.me'),
                    'date' => $end,
                ]);
            }

            LeaveRequest::query()
                ->select(['id', 'start_at', 'end_at'])
                ->whereHas('leaveBalance.contract', fn (Builder $contract) => $contract->where('employee_id', $employeeId))
                ->where('status', LeaveRequestStatus::Pending->value)
                ->where('start_at', '<', $today)
                ->orderBy('start_at')
                ->limit(self::WaitingLeaves)
                ->get()
                ->each(fn (LeaveRequest $leave) => $notes->push([
                    'key' => 'at-leave-'.$leave->id,
                    'title' => (string) __('hr_desk.cards.attention.leave'),
                    'meta' => $this->span($leave->start_at, $leave->end_at),
                    'badge' => (string) __('hr_desk.cards.attention.still_waiting'),
                    'href' => $this->link('hr.leave.requests.index'),
                    'date' => CarbonImmutable::parse($leave->start_at),
                ]));

            return $notes;
        });
    }
}
