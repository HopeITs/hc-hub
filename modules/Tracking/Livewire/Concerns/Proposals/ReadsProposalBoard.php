<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Proposals;

use App\Livewire\Concerns\Desk\PagesRegisterCalendar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Modules\Tracking\Enums\ProposalStatus;
use Modules\Tracking\Models\Proposal;

/**
 * The proposals register's Board (one column per status) and Calendar
 * (follow-up dates and planned starts) views, over the same filters as the
 * table. The requested budget is money, so it loads only for a viewer who
 * may see agreements.
 */
trait ReadsProposalBoard
{
    use PagesRegisterCalendar;

    /** Cards per Board column (desk.js slices 40). */
    public const BOARD_COLUMN_LIMIT = 40;

    /**
     * One column per live status. Deprecated cases are not columns: `on hold`
     * is a flag on the status now (W-06, D-58) and `final reports` was retired
     * by T04, so both would stand empty for ever — contracts.md §1 requires
     * every picker, filter and chart to iterate live().
     *
     * @return list<array{key: string, label: string, count: int, items: Collection<int, Proposal>}>
     */
    #[Computed]
    public function boardColumns(): array
    {
        $proposals = $this->boardQuery()->orderBy('proposals.name')->get();

        return array_map(function (ProposalStatus $status) use ($proposals): array {
            $inColumn = $proposals->filter(fn (Proposal $proposal): bool => $proposal->status === $status)->values();

            return [
                'key' => $status->value,
                'label' => $status->label(),
                'count' => $inColumn->count(),
                'items' => $inColumn->take(self::BOARD_COLUMN_LIMIT),
            ];
        }, ProposalStatus::live());
    }

    /**
     * @return list<array{date: string, label: string, href: string}>
     */
    #[Computed]
    public function calendarItems(): array
    {
        $start = $this->calendarMonthStart();
        $end = $start->endOfMonth();
        $items = [];

        $proposals = $this->boardQuery()
            ->where(fn (Builder $query) => $query
                ->whereBetween('proposals.status_remind_on', [$start->toDateString(), $end->toDateString()])
                ->orWhereBetween('proposals.planned_date_begin', [$start->toDateString(), $end->toDateString()]))
            ->orderBy('proposals.name')
            ->get();

        foreach ($proposals as $proposal) {
            $href = route('proposals.show', $proposal);

            if ($proposal->status_remind_on !== null && $proposal->status_remind_on->betweenIncluded($start, $end)) {
                $items[] = ['date' => $proposal->status_remind_on->toDateString(), 'label' => $proposal->name.' · '.__('desk.register.follow_up'), 'href' => $href];
            }

            if ($proposal->planned_date_begin->betweenIncluded($start, $end)) {
                $items[] = ['date' => $proposal->planned_date_begin->toDateString(), 'label' => $proposal->name.' · '.__('proposals.planned_start'), 'href' => $href];
            }
        }

        return $items;
    }

    /**
     * @return Builder<Proposal>
     */
    protected function boardQuery(): Builder
    {
        $columns = ['proposals.id', 'proposals.slug', 'proposals.name', 'proposals.status', 'proposals.kind', 'proposals.on_hold_since', 'proposals.status_remind_on', 'proposals.planned_date_begin', 'proposals.donor_id'];

        if ($this->canViewAgreements()) {
            array_push($columns, 'proposals.requested_budget', 'proposals.requested_currency');
        }

        return $this->filteredQuery()
            ->select($columns)
            ->with($this->canViewAgreements() ? ['donor:id,name'] : []);
    }
}
