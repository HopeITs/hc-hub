<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Checklists;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\ChecklistItem;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Checklists\ChecklistPeople;
use Modules\Hr\Support\Checklists\ChecklistWorkflow;

/**
 * The queries behind the four checklist lists. Each is scoped to the signed-in
 * employee (or, for "done", to their service department); a user with no
 * employee record gets empty lists.
 */
trait ReadsChecklistLists
{
    private const int DONE_PER_PAGE = 25;

    private function employeeId(): ?int
    {
        return Auth::user()?->employee_id;
    }

    /**
     * @return Builder<Checklist>
     */
    private function listQuery(): Builder
    {
        $names = implode(',', ChecklistPeople::NAME_COLUMNS);

        return Checklist::query()
            ->select(['id', 'item_id', 'employee_id', 'delivered_id', 'receiver_id', 'delivered_at', 'delivered_status', 'returned_at', 'returned_status', 'notes', 'created_at', 'updated_at'])
            ->with(["employee:{$names}", 'item:id,name']);
    }

    /**
     * Everything assigned to me, newest change first.
     *
     * @return Collection<int, Checklist>
     */
    #[Computed]
    public function myItems(): Collection
    {
        if ($this->employeeId() === null) {
            return new Collection;
        }

        return $this->listQuery()
            ->where('employee_id', $this->employeeId())
            ->latest('updated_at')
            ->limit(200)
            ->get();
    }

    /**
     * Items waiting for me to hand over.
     *
     * @return Collection<int, Checklist>
     */
    #[Computed]
    public function pendingDelivers(): Collection
    {
        if ($this->employeeId() === null) {
            return new Collection;
        }

        return $this->listQuery()
            ->where('delivered_id', $this->employeeId())
            ->where('delivered_status', ChecklistWorkflow::PENDING)
            ->latest()
            ->limit(200)
            ->get();
    }

    /**
     * Items waiting for me to take back.
     *
     * @return Collection<int, Checklist>
     */
    #[Computed]
    public function pendingReturns(): Collection
    {
        if ($this->employeeId() === null) {
            return new Collection;
        }

        return $this->listQuery()
            ->where('receiver_id', $this->employeeId())
            ->where('returned_status', ChecklistWorkflow::PENDING)
            ->latest()
            ->limit(200)
            ->get();
    }

    /**
     * The viewer's service department and its people; null when they are in none.
     *
     * @return array{department: string, employeeIds: list<int>}|null
     */
    #[Computed]
    public function departmentContext(): ?array
    {
        $user = Auth::user();

        return $user === null ? null : ChecklistPeople::departmentContext($user);
    }

    /**
     * Hand-overs and returns the viewer's department has completed.
     *
     * @return Builder<Checklist>
     */
    private function doneQuery(): Builder
    {
        $context = $this->departmentContext;

        if ($context === null) {
            return Checklist::query()->whereRaw('1 = 0');
        }

        $ids = $context['employeeIds'];

        return Checklist::query()->where(fn (Builder $query) => $query
            ->where(fn (Builder $delivered) => $delivered->whereIn('delivered_id', $ids)->where('delivered_status', ChecklistWorkflow::DELIVERED))
            ->orWhere(fn (Builder $received) => $received->whereIn('receiver_id', $ids)->where('returned_status', ChecklistWorkflow::RECEIVED)));
    }

    /**
     * @return LengthAwarePaginator<int, Checklist>
     */
    #[Computed]
    public function doneRequests(): LengthAwarePaginator
    {
        $names = implode(',', ChecklistPeople::NAME_COLUMNS);
        $search = trim($this->search);

        return $this->doneQuery()
            ->select(['id', 'item_id', 'employee_id', 'delivered_id', 'receiver_id', 'delivered_at', 'delivered_status', 'returned_at', 'returned_status', 'notes', 'created_at'])
            ->with(["employee:{$names}", "delivered:{$names}", "receiver:{$names}", 'item:id,name'])
            ->when($this->action === 'received', fn (Builder $query) => $query->where('returned_status', ChecklistWorkflow::RECEIVED))
            ->when($this->action === 'delivered', fn (Builder $query) => $query
                ->where('delivered_status', ChecklistWorkflow::DELIVERED)
                ->where(fn (Builder $open) => $open->whereNull('returned_status')->orWhere('returned_status', '!=', ChecklistWorkflow::RECEIVED)))
            ->when($this->item !== '', fn (Builder $query) => $query->where('item_id', (int) $this->item))
            ->when($this->person !== '', fn (Builder $query) => $query->where(fn (Builder $who) => $who
                ->where('delivered_id', (int) $this->person)
                ->orWhere('receiver_id', (int) $this->person)))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $match) => $match
                ->whereLike('notes', "%{$search}%")
                ->orWhereHas('item', fn (Builder $item) => $item->whereLike('name', "%{$search}%"))
                ->orWhereHas('employee', fn (Builder $employee) => $employee->search($search))
                ->orWhereHas('delivered', fn (Builder $employee) => $employee->search($search))
                ->orWhereHas('receiver', fn (Builder $employee) => $employee->search($search))))
            ->orderByRaw('COALESCE(returned_at, delivered_at) DESC')
            ->orderByDesc('id')
            ->paginate(self::DONE_PER_PAGE);
    }

    /**
     * The people of the viewer's department, for the "done by" filter.
     *
     * @return Collection<int, Employee>
     */
    #[Computed]
    public function departmentPeople(): Collection
    {
        $context = $this->departmentContext;

        if ($context === null) {
            return new Collection;
        }

        return Employee::query()
            ->whereIn('id', $context['employeeIds'])
            ->orderBy(Employee::translatedColumn('first_name'))
            ->get(ChecklistPeople::NAME_COLUMNS);
    }

    /**
     * @return Collection<int, ChecklistItem>
     */
    #[Computed]
    public function itemOptions(): Collection
    {
        return ChecklistItem::query()->orderBy('order')->orderBy('name')->get(['id', 'name']);
    }
}
