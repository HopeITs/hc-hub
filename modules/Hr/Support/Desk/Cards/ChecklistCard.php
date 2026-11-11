<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

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
        return [$this->figure(__('hr_desk.cards.checklist.with_me'), $this->mine($user)->count(), 'active')];
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

        return once(fn (): Collection => Checklist::query()
            ->select(['id', 'item_id', 'employee_id', 'brand', 'model', 'serial', 'delivered_at', 'delivered_status', 'returned_status'])
            ->where('employee_id', $employeeId)
            ->where('delivered_status', 'delivered')
            ->with('item:id,name')
            ->orderByDesc('delivered_at')
            ->limit(self::RowLimit)
            ->get());
    }
}
