<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\Employee;

/**
 * Pending requests: the items the viewer was asked to deliver to a colleague
 * and has not delivered yet, newest ask first.
 */
final class PendingRequestsCard extends HrCard
{
    public function key(): string
    {
        return 'pending-requests';
    }

    public function allows(User $user): bool
    {
        return $user->employee_id !== null && $user->can('checklists.deliver');
    }

    public function register(): ?string
    {
        return 'hr.checklists.index';
    }

    public function icon(): string
    {
        return 'board';
    }

    public function layout(): string
    {
        return 'people';
    }

    public function figures(User $user): array
    {
        $employeeId = $this->viewer($user)->employeeId();
        $waiting = $employeeId === null ? 0 : $this->waitingOn($employeeId)->count();

        return [$this->figure(__('hr_desk.cards.pending-requests.waiting'), $waiting, 'warn')];
    }

    public function health(User $user): ?string
    {
        return $this->pending($user)->isNotEmpty() ? 'warn' : null;
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->pending($user)->map(function (Checklist $ask): array {
            $name = (string) ($ask->employee?->name ?? '—');

            return $this->shaped(
                $this->row('pr-'.$ask->id, (string) ($ask->item?->name ?? '—'), $name, $ask->created_at === null ? null : ['label' => $ask->created_at->translatedFormat('j M'), 'tone' => 'warn'], $this->link('hr.checklists.index'), (string) $ask->employee?->secondary_name),
                lead: $this->face((int) $ask->employee_id, $name),
            );
        });
    }

    /**
     * @return Collection<int, Checklist>
     */
    private function pending(User $user): Collection
    {
        $employeeId = $this->viewer($user)->employeeId();

        if ($employeeId === null) {
            return new Collection;
        }

        return once(fn (): Collection => $this->waitingOn($employeeId)
            ->select(['id', 'item_id', 'employee_id', 'delivered_id', 'delivered_status', 'created_at'])
            ->with(['item:id,name', 'employee:'.implode(',', Employee::NameColumns)])
            ->latest()
            ->limit(self::RowLimit)
            ->get());
    }

    /**
     * The requests waiting on the employee to hand an item over, as the figure counts them and the list shows them.
     *
     * @return Builder<Checklist>
     */
    private function waitingOn(int $employeeId): Builder
    {
        return Checklist::query()->where('delivered_id', $employeeId)->where('delivered_status', 'pending');
    }
}
