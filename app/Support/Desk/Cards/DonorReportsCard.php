<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Enums\DonorReportState;
use App\Models\DonorReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Donor reports in card form: what is late, what falls due next and what
 * went out, over the reports the viewer may open (DonorReportPolicy::view,
 * as the donor reports page reads it).
 */
final class DonorReportsCard extends PageCard
{
    public function route(): string
    {
        return 'donor-reports.index';
    }

    public function figures(User $user): array
    {
        $today = CarbonImmutable::today();

        return [
            $this->figure(DonorReportState::Overdue->label(), $this->visible($user)->whereNull('submitted_on')->whereDate('due_on', '<', $today)->count(), 'critical'),
            $this->figure(__('desk-workspace.card.figure.due_30'), $this->visible($user)->whereNull('submitted_on')->whereBetween('due_on', [$today, $today->addDays(30)])->count(), 'warn'),
            $this->figure(DonorReportState::Submitted->label(), $this->visible($user)->whereNotNull('submitted_on')->count(), 'good'),
        ];
    }

    public function breakdown(User $user): array
    {
        $today = CarbonImmutable::today()->toDateString();
        $overdue = $this->visible($user)->whereNull('submitted_on')->whereDate('due_on', '<', $today)->count();
        $due = $this->visible($user)->whereNull('submitted_on')->whereDate('due_on', '>=', $today)->count();
        $sent = $this->visible($user)->whereNotNull('submitted_on')->count();

        return $this->parts([
            ['label' => DonorReportState::Overdue->label(), 'count' => $overdue, 'tone' => DonorReportState::Overdue->tone()->value],
            ['label' => DonorReportState::Due->label(), 'count' => $due, 'tone' => DonorReportState::Due->tone()->value],
            ['label' => DonorReportState::Submitted->label(), 'count' => $sent, 'tone' => DonorReportState::Submitted->tone()->value],
        ]);
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->visible($user)->with('donorProposal.donor:id,name')
            ->orderByRaw('submitted_on is not null')->orderBy('due_on')->limit(self::RowLimit)->get()
            ->map(function (DonorReport $report): array {
                $state = $report->isSubmitted() ? DonorReportState::Submitted : ($report->isOverdue() ? DonorReportState::Overdue : DonorReportState::Due);

                return $this->row(
                    'dr-'.$report->id,
                    $report->title,
                    collect([$report->donorProposal?->donor?->name, __('desk-workspace.card.due_on', ['date' => $report->due_on->translatedFormat(__('formats.date'))])])->filter()->implode(' · '),
                    $this->badgeOf($state),
                    route('donor-reports.show', $report),
                );
            });
    }

    /**
     * @return Builder<DonorReport>
     */
    private function visible(User $user): Builder
    {
        return DonorReport::query()->unless(
            $user->isAdministrator() || $user->can('funding-agreements.view') || $user->can('reports.aggregate'),
            fn (Builder $query) => $query->where('responsible_employee_id', $user->employee_id ?? 0),
        );
    }
}
