<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\DonorReportState;
use Modules\Tracking\Models\DonorReport;

/**
 * Donor reports in card form: what is late, what falls due next and what
 * went out, over the reports the viewer may open (DonorReportPolicy::view,
 * as the donor reports page reads it).
 */
final class DonorReportsCard extends PageCard
{
    /**
     * How many weeks ahead the hero charts what falls due.
     */
    private const int HeroWeeks = 8;

    public function route(): string
    {
        return 'donor-reports.index';
    }

    public function figures(User $user): array
    {
        $counts = $this->counts($user);

        return [
            $this->figure(DonorReportState::Overdue->label(), $counts['overdue'], 'critical'),
            $this->figure(__('desk-workspace.card.figure.due_30'), $counts['due_30'], 'warn'),
            $this->figure(DonorReportState::Submitted->label(), $counts['sent'], 'good'),
        ];
    }

    public function breakdown(User $user): array
    {
        $counts = $this->counts($user);

        return $this->parts([
            ['label' => DonorReportState::Overdue->label(), 'count' => $counts['overdue'], 'tone' => DonorReportState::Overdue->tone()->value],
            ['label' => DonorReportState::Due->label(), 'count' => $counts['due'], 'tone' => DonorReportState::Due->tone()->value],
            ['label' => DonorReportState::Submitted->label(), 'count' => $counts['sent'], 'tone' => DonorReportState::Submitted->tone()->value],
        ]);
    }

    public function hero(User $user): ?array
    {
        $today = CarbonImmutable::today();
        $counts = $this->counts($user);
        $weeks = [];

        foreach ($counts['weeks'] as $week => $count) {
            $weeks[] = ['label' => $today->addWeeks($week)->translatedFormat('j/n'), 'value' => (string) $count, 'count' => $count, 'tone' => $week < 2 && $count > 0 ? 'warn' : 'active'];
        }

        return [
            'kind' => 'weeks',
            'big' => $this->number($counts['overdue']),
            'caption' => (string) DonorReportState::Overdue->label(),
            'tone' => $counts['overdue'] > 0 ? 'critical' : 'good',
            'items' => $weeks,
        ];
    }

    public function layout(): string
    {
        return 'agenda';
    }

    protected function records(User $user, string $search): Collection
    {
        return $this->visible($user)->with('donorProposal.donor:id,name')
            ->orderByRaw('submitted_on is not null')->orderBy('due_on')->limit(self::RowLimit)->get()
            ->map(function (DonorReport $report): array {
                $state = $report->isSubmitted() ? DonorReportState::Submitted : ($report->isOverdue() ? DonorReportState::Overdue : DonorReportState::Due);
                $badge = $this->badgeOf($state);

                return $this->shaped(
                    $this->row(
                        'dr-'.$report->id,
                        $report->title,
                        (string) ($report->donorProposal?->donor?->name ?? ''),
                        $badge,
                        route('donor-reports.show', $report),
                    ),
                    lead: ['kind' => 'date', 'big' => $report->due_on->translatedFormat('j'), 'small' => $report->due_on->translatedFormat('M y'), 'tone' => $badge['tone']],
                );
            });
    }

    /**
     * Every figure, the breakdown and the hero's eight weeks in one query
     * over the reports the viewer may open, read once per card.
     *
     * @return array{overdue: int, due_30: int, due: int, sent: int, weeks: list<int>}
     */
    private function counts(User $user): array
    {
        return once(function () use ($user): array {
            $today = CarbonImmutable::today();
            $open = 'submitted_on is null and ';
            $columns = [
                'overdue' => [$open.'due_on < ?', [$today->toDateString()]],
                'due_30' => [$open.'due_on between ? and ?', [$today->toDateString(), $today->addDays(30)->toDateString()]],
                'due' => [$open.'due_on >= ?', [$today->toDateString()]],
                'sent' => ['submitted_on is not null', []],
            ];

            for ($week = 0; $week < self::HeroWeeks; $week++) {
                $columns['week_'.$week] = [$open.'due_on >= ? and due_on < ?', [$today->addWeeks($week)->toDateString(), $today->addWeeks($week + 1)->toDateString()]];
            }

            $query = $this->visible($user)->toBase();

            foreach ($columns as $name => [$condition, $bindings]) {
                $query->selectRaw("sum(case when {$condition} then 1 else 0 end) as {$name}", $bindings);
            }

            $row = (array) $query->first();

            return [
                'overdue' => (int) ($row['overdue'] ?? 0),
                'due_30' => (int) ($row['due_30'] ?? 0),
                'due' => (int) ($row['due'] ?? 0),
                'sent' => (int) ($row['sent'] ?? 0),
                'weeks' => array_map(fn (int $week): int => (int) ($row['week_'.$week] ?? 0), range(0, self::HeroWeeks - 1)),
            ];
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
