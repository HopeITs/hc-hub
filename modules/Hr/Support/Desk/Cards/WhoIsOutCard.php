<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Support\Desk\TeamLeaves;

/**
 * Who is out: the people of the viewer's team on an accepted leave today,
 * the viewer left out. The leave type is shown, never the reason.
 */
final class WhoIsOutCard extends HrCard
{
    public function key(): string
    {
        return 'who-is-out';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null;
    }

    public function register(): ?string
    {
        return 'hr.team.index';
    }

    public function layout(): string
    {
        return 'people';
    }

    public function figures(User $user): array
    {
        return [
            $this->figure(__('hr_desk.cards.who-is-out.out_today'), $this->out($user)->count(), 'warn'),
            $this->figure(__('hr_desk.cards.who-is-out.team'), count($this->viewer($user)->othersIds())),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->out($user)->map(function (LeaveRequest $leave): array {
            $employee = TeamLeaves::employeeOf($leave);
            $name = (string) ($employee?->name ?? '—');

            return $this->shaped(
                $this->row('wo-'.$leave->id, $name, (string) ($leave->leaveBalance?->leaveType?->name ?? ''), ['label' => $leave->type === 'hour' ? $leave->start_at->translatedFormat('H:i').' – '.$leave->end_at->translatedFormat('H:i') : (string) __('hr_desk.until', ['date' => $leave->end_at->translatedFormat('j M')]), 'tone' => 'warn'], $this->link('hr.team.index'), (string) $employee?->secondary_name),
                lead: $this->face((int) $employee?->id, $name),
            );
        });
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    private function out(User $user): Collection
    {
        $today = CarbonImmutable::today();

        return once(fn (): Collection => app(TeamLeaves::class)->between($this->viewer($user)->othersIds(), $today, $today)
            ->unique(fn (LeaveRequest $leave): int => (int) TeamLeaves::employeeOf($leave)?->id)
            ->sortBy(fn (LeaveRequest $leave): string => (string) TeamLeaves::employeeOf($leave)?->name)
            ->values());
    }
}
