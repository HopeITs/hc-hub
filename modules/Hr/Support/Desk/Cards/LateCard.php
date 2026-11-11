<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Inbox\WorkInbox;

/**
 * Late: what is already past its moment — a leave that started while still
 * undecided, and (HR and the general manager, with contracts.view) a contract
 * that ended in the last thirty days with nothing newer for someone still on
 * staff. The oldest first.
 */
final class LateCard extends HrCard
{
    public const int NotRenewedDays = 30;

    public function key(): string
    {
        return 'late';
    }

    public function allows(User $user): bool
    {
        return app(WorkInbox::class)->hasSources($user) || $this->seesEndedContracts($user);
    }

    public function register(): ?string
    {
        return 'hr.inbox';
    }

    public function icon(): string
    {
        return 'flag';
    }

    public function layout(): string
    {
        return 'people';
    }

    public function figures(User $user): array
    {
        $items = $this->items($user);

        return [
            $this->figure(__('hr_desk.cards.late.leaves'), $items->where('kind', 'leave')->count(), 'critical'),
            $this->figure(__('hr_desk.cards.late.contracts'), $items->where('kind', 'contract')->count(), 'warn'),
        ];
    }

    public function health(User $user): ?string
    {
        return $this->items($user)->isNotEmpty() ? 'critical' : null;
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->items($user)->map(fn (array $item): array => $this->shaped(
            $this->row($item['key'], $item['name'], (string) __('hr_desk.cards.late.'.$item['kind'].'_line', ['date' => $item['since']->translatedFormat('j M')]), ['label' => trans_choice('hr_desk.days_ago', max(1, (int) $item['since']->diffInDays(today())), ['count' => max(1, (int) $item['since']->diffInDays(today()))]), 'tone' => 'critical'], $item['href'], $item['words']),
            lead: $this->face($item['id'], $item['name']),
        ));
    }

    /**
     * @return Collection<int, array{key: string, kind: string, id: int, name: string, words: string, since: CarbonImmutable, href: string}>
     */
    private function items(User $user): Collection
    {
        return once(function () use ($user): Collection {
            $items = app(WorkInbox::class)->hasSources($user)
                ? app(WorkInbox::class)->for($user)['rows']->where('started', true)->map(fn (array $row): array => [
                    'key' => 'lt-'.$row['key'],
                    'kind' => 'leave',
                    'id' => (int) $row['employee_id'],
                    'name' => $row['name'],
                    'words' => $row['words'],
                    'since' => CarbonImmutable::instance($row['from'])->startOfDay(),
                    'href' => $row['href'] !== '' ? $row['href'] : $this->link('hr.inbox'),
                ])->values()
                : collect();

            if ($this->seesEndedContracts($user)) {
                $cities = $this->viewer($user)->cityScope();
                $items = $items->concat(Contract::query()
                    ->select(['contracts.id', 'contracts.employee_id', 'contracts.start_date', 'contracts.end_date'])
                    ->inView('not-renewed', self::NotRenewedDays)
                    ->when($cities !== null, fn (Builder $query) => $query->whereHas('employee', fn (Builder $employee) => $employee->whereIn('city_id', $cities)))
                    ->with('employee:'.implode(',', Employee::NameColumns))
                    ->orderBy('contracts.end_date')
                    ->limit(self::RowLimit)
                    ->get()
                    ->map(fn (Contract $contract): array => [
                        'key' => 'lt-contract-'.$contract->id,
                        'kind' => 'contract',
                        'id' => (int) $contract->employee_id,
                        'name' => (string) ($contract->employee?->name ?? '—'),
                        'words' => (string) $contract->employee?->secondary_name,
                        'since' => CarbonImmutable::instance($contract->end_date)->startOfDay()->addDay(),
                        'href' => $this->link('hr.contracts.index', ['view' => 'not-renewed']),
                    ]));
            }

            return $items->sortBy(fn (array $item): int => $item['since']->getTimestamp())->values();
        });
    }

    private function seesEndedContracts(User $user): bool
    {
        $viewer = $this->viewer($user);

        return ($viewer->isHr() || $viewer->seesOrganisation()) && $user->can('contracts.view');
    }
}
