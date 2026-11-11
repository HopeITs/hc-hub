<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;

/**
 * My team: the viewer's direct manager first, then the people of their team
 * (HrViewer::teamIds) by job level, each with their position.
 */
final class TeamCard extends HrCard
{
    public function key(): string
    {
        return 'team';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null;
    }

    public function register(): ?string
    {
        return 'hr.team.index';
    }

    public function size(): string
    {
        return 'L';
    }

    public function layout(): string
    {
        return 'people';
    }

    public function figures(User $user): array
    {
        $members = $this->members($user);

        return [
            $this->figure(__('hr_desk.cards.team.people'), $members->count(), 'active'),
            $this->figure(__('hr_desk.cards.team.reports'), count($this->viewer($user)->reportIds())),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->members($user)->map(fn (array $member): array => $this->shaped(
            $this->row('tm-'.$member['id'], $member['name'], $member['title'], $member['manager'] ? ['label' => (string) __('hr_desk.cards.team.manager'), 'tone' => 'active'] : null, $this->link('hr.team.index'), $member['words']),
            lead: $this->face($member['id'], $member['name']),
        ));
    }

    /**
     * @return Collection<int, array{id: int, name: string, words: string, title: string, manager: bool}>
     */
    private function members(User $user): Collection
    {
        $viewer = $this->viewer($user);
        $ids = $viewer->othersIds();
        $managerPositionId = $viewer->contract()?->position?->direct_manager_id;

        return collect(Cache::remember('hr.desk.team-card.'.$user->id.'.'.app()->getLocale().'.'.md5(implode(',', $ids).'|'.$managerPositionId), 300, function () use ($ids, $managerPositionId): array {
            $title = app()->getLocale() === 'ar' ? 'title_ar' : 'title_en';
            $read = fn () => Contract::query()->activeNow()
                ->select(['contracts.id', 'contracts.employee_id', 'contracts.position_id', 'contracts.start_date'])
                ->whereHas('employee', fn ($employee) => $employee->where('is_active', true))
                ->with(['employee:'.implode(',', Employee::NameColumns), 'position:id,title_en,title_ar,job_level_id', 'position.jobLevel:id,grade']);

            $manager = $managerPositionId === null ? null : $read()->where('contracts.position_id', $managerPositionId)->latest('contracts.start_date')->first();
            $team = $ids === [] ? collect() : $read()->whereIn('contracts.employee_id', $ids)->limit(self::RowLimit * 2)->get();

            $row = fn (Contract $contract, bool $isManager): array => [
                'id' => (int) $contract->employee_id,
                'name' => (string) ($contract->employee?->name ?? '—'),
                'words' => (string) $contract->employee?->secondary_name,
                'title' => (string) ($contract->position?->{$title} ?: $contract->position?->title_en),
                'grade' => (int) ($contract->position?->jobLevel?->grade ?? 999),
                'manager' => $isManager,
            ];

            return collect($manager === null ? [] : [$row($manager, true)])
                ->concat($team->map(fn (Contract $contract): array => $row($contract, false))->sortBy([['grade', 'asc'], ['name', 'asc']]))
                ->unique('id')
                ->take(self::RowLimit)
                ->values()
                ->all();
        }));
    }
}
