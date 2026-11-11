<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Support\Desk\HrStatistics;

/**
 * No leave taken: active employees on a running contract who have never
 * asked for annual leave, for HR to follow up.
 */
final class NoLeavesCard extends HrCard
{
    public function key(): string
    {
        return 'no-leaves';
    }

    public function allows(User $user): bool
    {
        return $user->can('statistics.view');
    }

    public function register(): ?string
    {
        return 'hr.statistics.index';
    }

    public function icon(): string
    {
        return 'chart';
    }

    public function layout(): string
    {
        return 'people';
    }

    public function figures(User $user): array
    {
        return [$this->figure(__('hr_desk.cards.no-leaves.people'), count($this->people($user)), 'warn')];
    }

    protected function records(User $user, string $search): Collection
    {
        return collect($this->people($user))->map(fn (array $person): array => $this->shaped(
            $this->row('nl-'.$person['id'], $person['name'], '', null, $this->link('hr.employees.show', $person['id']), $person['words']),
            lead: $this->face($person['id'], $person['name']),
        ));
    }

    /**
     * @return list<array{id: int, name: string, words: string}>
     */
    private function people(User $user): array
    {
        return app(HrStatistics::class)->withoutLeaves($this->viewer($user)->cityScope());
    }
}
