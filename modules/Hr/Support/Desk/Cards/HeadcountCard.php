<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Support\Desk\HrStatistics;

/**
 * A head count split one way (by department, job level or contract type),
 * the largest first, with the HR totals above it. For people with
 * statistics.view, narrowed to their governorates.
 */
abstract class HeadcountCard extends HrCard
{
    /**
     * The split this card shows.
     *
     * @param  list<int>|null  $cities
     * @return list<array{label: string, count: int}>
     */
    abstract protected function split(HrStatistics $statistics, ?array $cities): array;

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
        return 'rank';
    }

    public function figures(User $user): array
    {
        $totals = app(HrStatistics::class)->totals($this->viewer($user)->cityScope());

        return [
            $this->figure(__('hr_desk.stats.active_employees'), $totals['active'], 'active'),
            $this->figure(__('hr_desk.stats.running_contracts'), $totals['contracts'], 'good'),
            $this->figure(__('hr_desk.stats.ending_soon'), $totals['ending'], 'warn'),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        $parts = collect($this->split(app(HrStatistics::class), $this->viewer($user)->cityScope()))
            ->filter(fn (array $part): bool => $part['count'] > 0)
            ->sortByDesc('count')
            ->values();
        $largest = (int) $parts->max('count');

        return $parts->map(fn (array $part, int $i): array => $this->shaped(
            $this->row($this->key().'-'.$i, $part['label'], '', null, $this->link('hr.statistics.index')),
            lead: ['kind' => 'rank', 'big' => (string) ($i + 1), 'tone' => $i < 3 ? 'active' : 'neutral'],
            end: ['big' => $this->number($part['count'])],
            bar: ['to' => $this->share($part['count'], $largest), 'tone' => 'active'],
        ));
    }
}
