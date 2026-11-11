<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\Checklist;

/**
 * My checklist: the items delivered to the viewer (laptop, phone, keys…) and
 * still in their hands, newest first.
 */
final class ChecklistCard extends HrCard
{
    public function key(): string
    {
        return 'checklist';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null && ($user->can('checklists.view-own') || $user->can('checklists.view-all'));
    }

    public function register(): ?string
    {
        return 'hr.checklists.index';
    }

    public function icon(): string
    {
        return 'check';
    }

    public function size(): string
    {
        return 'S';
    }

    public function figures(User $user): array
    {
        $employeeId = $this->viewer($user)->employeeId();
        $withMe = $employeeId === null ? 0 : $this->withMe($employeeId)->count();

        return [$this->figure(__('hr_desk.cards.checklist.with_me'), $withMe, 'active')];
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->mine($user)->map(fn (Checklist $item): array => $this->row(
            'ck-'.$item->id,
            (string) ($item->item?->name ?? '—'),
            trim(implode(' · ', array_filter([(string) $item->brand, (string) $item->model, (string) $item->serial]))),
            $item->delivered_at === null ? null : ['label' => $item->delivered_at->translatedFormat('j M Y'), 'tone' => 'neutral'],
            $this->link('hr.checklists.index'),
        ));
    }

    /**
     * @return Collection<int, Checklist>
     */
    private function mine(User $user): Collection
    {
        $employeeId = $this->viewer($user)->employeeId();

        if ($employeeId === null) {
            return new Collection;
        }

        return once(fn (): Collection => $this->withMe($employeeId)
            ->select(['id', 'item_id', 'employee_id', 'brand', 'model', 'serial', 'delivered_at', 'delivered_status', 'returned_status'])
            ->with('item:id,name')
            ->orderByDesc('delivered_at')
            ->limit(self::RowLimit)
            ->get());
    }

    /**
     * The items handed to the employee, as the figure counts them and the list shows them.
     *
     * @return Builder<Checklist>
     */
    private function withMe(int $employeeId): Builder
    {
        return Checklist::query()->where('employee_id', $employeeId)->where('delivered_status', 'delivered');
    }
}
